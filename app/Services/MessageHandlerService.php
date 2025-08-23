<?php

namespace App\Services;

use App\Models\Channel;
use Illuminate\Http\UploadedFile;

class MessageHandlerService
{
    protected $aiResponseService;
    protected $tokenPricingService;
    protected $mediaProcessingService;

    public function __construct(
        AIResponseService $aiResponseService,
        TokenPricingService $tokenPricingService,
        MediaProcessingService $mediaProcessingService
    ) {
        $this->aiResponseService = $aiResponseService;
        $this->tokenPricingService = $tokenPricingService;
        $this->mediaProcessingService = $mediaProcessingService;
    }

    /**
     * Handle incoming message from any platform
     *
     * @param int|null $channelId
     * @param string $sender
     * @param string|null $messageContent
     * @param UploadedFile|null $mediaFile
     * @param \App\Models\Bot|null $bot
     * @param string $format
     * @return array
     * @throws \Exception
     */
    public function handleMessage(?int $channelId, string $sender, ?string $messageContent = null, ?UploadedFile $mediaFile = null, $bot = null, string $format = 'html'): array
    {
        // Process media if provided
        $media = null;
        if ($mediaFile) {
            $media = $this->mediaProcessingService->process($mediaFile, $messageContent);
        }

        $channel = null;
        
        if ($channelId) {
            $channel = Channel::with(['bots', 'team.balance'])->findOrFail($channelId);

            $bot = $channel->bots->first();

            if (!$bot) {
                throw new \Exception('No bot found for this channel');
            }
        }

        // Generate AI response
        $response = $this->aiResponseService->generateResponse($bot, $channel, $messageContent, $sender, $media, $format);

        return [
            'response' => $response,
            'channel' => $channel,
            'bot' => $bot,
            'media' => $media
        ];
    }

    /**
     * Validate message data
     *
     * @param array $data
     * @return array
     */
    public function validateMessageData(array $data): array
    {
        $rules = [
            'channelId' => 'required|integer',
            'sender' => 'required|string',
            'message' => 'nullable|string',
            'media_file' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,webp,mp3,wav,ogg,m4a,webm,flac,mp4,avi,mov,pdf,doc,docx,txt',
        ];

        return validator($data, $rules)->validate();
    }
}
