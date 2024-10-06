<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Events\IntegrationUpdated;
use App\Events\QrCodeUpdated;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();
        $integrationId = $data['integrationId'];

        $integration = Integration::findOrFail($integrationId);

        if (isset($data['status'])) {
            $integration->update(['is_connected' => $data['status'] === 'connected']);
        }

        // Broadcast the general update
        IntegrationUpdated::dispatch($integration);

        return response()->json(['message' => 'Webhook processed successfully']);
    }
}