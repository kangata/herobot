<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use App\Models\Channel;
use App\Services\AIResponseService;
use App\Services\MediaProcessingService;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class WhatsAppMessageController extends Controller
{
    protected $aiResponseService;
    protected $transactionService;
    protected $mediaProcessingService;

    public function __construct(
        AIResponseService $aiResponseService,
        TransactionService $transactionService,
        MediaProcessingService $mediaProcessingService
    ) {
        $this->aiResponseService = $aiResponseService;
        $this->transactionService = $transactionService;
        $this->mediaProcessingService = $mediaProcessingService;
    }

    public function handleIncomingMessage(Request $request)
    {
        $validated = $request->validate([
            'channelId' => 'required|integer',
            'sender' => 'required|string',
            'message' => 'nullable|string',
            'media_file' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,webp,mp3,wav,ogg,m4a,webm,flac,mp4,avi,mov,pdf,doc,docx,txt',
        ]);

        $channelId = $validated['channelId'];
        $sender = $validated['sender'];
        $messageContent = $validated['message'];
        
        $media = null;
        if ($request->hasFile('media_file')) {
            $media = $this->mediaProcessingService->process($request->file('media_file'), $messageContent);
        }

        $channel = Channel::with(['bots', 'team.balance'])->findOrFail($channelId);
        $bot = $channel->bots->first();

        if (!$bot) {
            return response()->json(['error' => 'No bot found for this channel'], 404);
        }

        $response = $this->aiResponseService->generateResponse($bot, $messageContent, $sender, $channelId, $media);

        // Record usage transaction and deduct credits
        $this->transactionService->recordUsage($channel->team);

        return response()->json(['response' => $response]);
    }

}
