<?php

namespace App\Http\Controllers;

use App\Http\Requests\EarlyAccessStoreRequest as StoreRequest;
use App\Mail\EarlyAccessConfirmation;
use App\Models\EarlyAccess;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EarlyAccessController extends Controller
{
    protected $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function store(StoreRequest $request)
    {
        // Store the application
        $application = EarlyAccess::create($request->validated());

        // TODO: Send notification to admin

        // Send confirmation email to applicant
        try {
            Mail::to($application->email)
                ->send(new EarlyAccessConfirmation($application));
        } catch (\Throwable $e) {
            // Log the error but don't stop the process
            Log::error('Failed to send confirmation email', [
                'error' => $e->getMessage(),
                'application_id' => $application->id,
            ]);
        }

        return back()->with('success', 'Your application has been submitted successfully. We will contact you soon!');
    }
}
