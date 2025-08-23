<?php

namespace App\Http\Controllers;

use App\Services\MessageHandlerService;
use Illuminate\Http\Request;

class WhatsAppMessageController extends Controller
{
    protected $messageHandlerService;

    public function __construct(MessageHandlerService $messageHandlerService)
    {
        $this->messageHandlerService = $messageHandlerService;
    }

    public function handleIncomingMessage(Request $request)
    {
        try {
            // Validate the request data
            $validated = $this->messageHandlerService->validateMessageData($request->all());

            $channelId = $validated['channelId'];
            $sender = $validated['sender'];
            $messageContent = $validated['message'] ?? null;
            $mediaFile = $request->hasFile('media_file') ? $request->file('media_file') : null;

            // Handle the message using the service
            $result = $this->messageHandlerService->handleMessage($channelId, $sender, $messageContent, $mediaFile, null, 'whatsapp');

            return response()->json(['response' => $result['response']]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
