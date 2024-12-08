<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Events\IntegrationUpdated;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();
        $integrationId = $data['integrationId'];

        $integration = Integration::findOrFail($integrationId);

        $updateData = [];

        if (isset($data['phone'])) {
            $updateData['phone'] = $data['phone'];
        }

        if (isset($data['status'])) {
            $updateData['is_connected'] = $data['status'] === 'connected';

            if (!$updateData['is_connected']) {
                $updateData['phone'] = null;
            }
        }

        $integration->update($updateData);

        // Broadcast the general update
        IntegrationUpdated::dispatch($integration);

        return response()->json(['message' => 'Webhook processed successfully']);
    }
}