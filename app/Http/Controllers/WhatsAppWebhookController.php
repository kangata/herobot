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

        if (isset($data['status']) && $data['status'] === 'connected') {
            $integration->update(['is_connected' => true]);
        }

        // Broadcast the general update
        IntegrationUpdated::dispatch($integration, $data);

        return response()->json(['message' => 'Webhook processed successfully']);
    }
}