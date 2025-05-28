<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EarlyAccess;
use App\Services\TelegramService;
use App\Mail\EarlyAccessConfirmation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EarlyAccessController extends Controller
{
    protected $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'website' => 'nullable|string|max:255',
            'organization_type' => 'required|string|in:school,social,business,other',
            'description' => 'required|string|max:1000',
        ]);

        // Store the application
        $application = EarlyAccess::create($validated);

        // TODO: Send notification to admin

        // Send confirmation email to applicant
        try {
            Mail::to($application->email)
                ->send(new EarlyAccessConfirmation($application));
        } catch (\Exception $e) {
            // Log the error but don't stop the process
            Log::error('Failed to send confirmation email', [
                'error' => $e->getMessage(),
                'application_id' => $application->id
            ]);
        }

        return back()->with('success', 'Your application has been submitted successfully. We will contact you soon!');
    }
} 