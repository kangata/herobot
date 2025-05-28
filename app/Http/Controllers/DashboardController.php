<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;

        // Get total messages for last 30 days
        $totalMessages = ChatHistory::whereHas('integration', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        })->where('created_at', '>=', now()->subDays(30))->count();

        // Get total conversations (unique chat histories grouped by sender)
        $totalConversations = ChatHistory::whereHas('integration', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        })
            ->where('created_at', '>=', now()->subDays(30))
            ->distinct('sender')
            ->count();

        // // Get credits usage for last 30 days
        // $creditsUsage = Transaction::where('team_id', $team->id)
        //     ->where('type', 'usage')
        //     ->where('created_at', '>=', now()->subDays(30))
        //     ->sum('amount');

        // Get daily message counts for the last 30 days
        $startDate = now()->subDays(30)->startOfDay();
        $dates = collect();
        for ($date = clone $startDate; $date <= now(); $date->addDay()) {
            $dates->push($date->format('Y-m-d'));
        }

        // Get daily message counts
        $dailyMessageStats = ChatHistory::whereHas('integration', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        })
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // Get daily conversation counts (unique senders per day)
        $dailyConversationStats = ChatHistory::whereHas('integration', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        })
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(DISTINCT sender) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // Fill in missing dates with 0 for both datasets
        $filledMessageStats = $dates->mapWithKeys(function ($date) use ($dailyMessageStats) {
            return [$date => $dailyMessageStats[$date] ?? 0];
        });

        $filledConversationStats = $dates->mapWithKeys(function ($date) use ($dailyConversationStats) {
            return [$date => $dailyConversationStats[$date] ?? 0];
        });

        return Inertia::render('Dashboard', [
            'stats' => [
                [
                    'name' => 'Total Messages',
                    'stat' => number_format($totalMessages),
                ],
                [
                    'name' => 'Total Conversations',
                    'stat' => number_format($totalConversations),
                ],
                // [
                //     'name' => 'Credits Usage',
                //     'stat' => 'Rp ' . number_format($creditsUsage, 0, ',', '.'),
                // ],
            ],
            'chartData' => [
                'dates' => $filledMessageStats->keys()->map(function ($date) {
                    return date('M j', strtotime($date));
                }),
                'messageCounts' => $filledMessageStats->values(),
                'conversationCounts' => $filledConversationStats->values(),
            ],
        ]);
    }
}
