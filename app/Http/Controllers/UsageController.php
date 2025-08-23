<?php

namespace App\Http\Controllers;

use App\Models\TokenUsage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UsageController extends Controller
{
    public function __construct()
    {
        if (config('app.edition') !== 'cloud') {
            abort(404);
        }
    }

    /**
     * Display the usage page with token usage statistics.
     */
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;
        
        // Get token usage data for the current team
        $usages = TokenUsage::where('team_id', $team->id)
            ->with('bot:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        // Calculate summary statistics
        $totalCredits = TokenUsage::where('team_id', $team->id)->sum('credits');
        $totalInputTokens = TokenUsage::where('team_id', $team->id)->sum('input_tokens');
        $totalOutputTokens = TokenUsage::where('team_id', $team->id)->sum('output_tokens');
        
        // Get usage by provider
        $usageByProvider = TokenUsage::where('team_id', $team->id)
            ->selectRaw('provider, SUM(credits) as total_credits, SUM(input_tokens) as total_input_tokens, SUM(output_tokens) as total_output_tokens')
            ->groupBy('provider')
            ->get();

        // Get usage by model
        $usageByModel = TokenUsage::where('team_id', $team->id)
            ->selectRaw('provider, model, SUM(credits) as total_credits, SUM(input_tokens) as total_input_tokens, SUM(output_tokens) as total_output_tokens, COUNT(*) as usage_count')
            ->groupBy('provider', 'model')
            ->orderBy('total_credits', 'desc')
            ->get();

        // Get daily usage for the last 30 days
        $dailyUsage = TokenUsage::where('team_id', $team->id)
            ->selectRaw('DATE(created_at) as date, SUM(credits) as total_credits, SUM(input_tokens) as total_input_tokens, SUM(output_tokens) as total_output_tokens')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return Inertia::render('Usage/Index', [
            'usages' => $usages,
            'summary' => [
                'total_credits' => round($totalCredits, 2),
                'total_input_tokens' => $totalInputTokens,
                'total_output_tokens' => $totalOutputTokens,
                'total_tokens' => $totalInputTokens + $totalOutputTokens,
            ],
            'usage_by_provider' => $usageByProvider,
            'usage_by_model' => $usageByModel,
            'daily_usage' => $dailyUsage,
        ]);
    }
}
