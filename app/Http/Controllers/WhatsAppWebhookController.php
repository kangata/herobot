<?php

namespace App\Http\Controllers;

use App\Events\ChannelUpdated;
use App\Models\Channel;
use Illuminate\Http\Request;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();
        $channelId = $data['channelId'];

        $channel = Channel::findOrFail($channelId);

        $updateData = [];

        if (isset($data['phone'])) {
            $updateData['phone'] = $data['phone'];
        }

        if (isset($data['status'])) {
            $updateData['is_connected'] = $data['status'] === 'connected';

            if (! $updateData['is_connected']) {
                $updateData['phone'] = null;
            }
        }

        $channel->update($updateData);

        // Broadcast the general update
        ChannelUpdated::dispatch($channel, $data['status'] ?? 'unknown');

        return response()->json(['message' => 'Webhook processed successfully']);
    }
}
