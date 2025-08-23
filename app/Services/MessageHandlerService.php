<?php

namespace App\Services;

use App\Models\Channel;
use App\Services\Traits\AIServiceHelperTrait;
use Illuminate\Http\UploadedFile;

class MessageHandlerService
{
    use AIServiceHelperTrait;

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
        $channel = null;
        
        if ($channelId) {
            $channel = Channel::with(['bots', 'team.balance'])->findOrFail($channelId);

            $bot = $channel->bots->first();

            if (!$bot) {
                throw new \Exception('No bot found for this channel');
            }
        }

        // Check if team has sufficient credits
        $isCloud = config('app.edition') === 'cloud';
        if ($isCloud && $bot && $bot->team && $bot->team->balance) {
            // Get current balance
            $currentBalance = $bot->team->balance->amount / 1000000;

            // Calculate estimated credits needed before processing
            $estimatedCredits = $this->calculateEstimatedCredits($bot, $messageContent, $mediaFile);
        
            if ($currentBalance < $estimatedCredits) {
                throw new \Exception(
                    sprintf(
                        'Insufficient credits. Required: %.2f, Available: %.2f', 
                        $estimatedCredits, 
                        $currentBalance
                    )
                );
            }
        }

        // Process media if provided (only after credit check)
        $media = null;
        if ($mediaFile) {
            $media = $this->mediaProcessingService->process($mediaFile, $messageContent);
        }

        // Generate AI response (only after credit check)
        $response = $this->aiResponseService->generateResponse($bot, $channel, $messageContent, $sender, $media, $format);

        return [
            'response' => $response,
            'channel' => $channel,
            'bot' => $bot,
            'media' => $media,
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

    /**
     * Calculate estimated credits needed for processing message and media
     *
     * @param \App\Models\Bot|null $bot
     * @param string|null $messageContent
     * @param UploadedFile|null $mediaFile
     * @return float
     */
    protected function calculateEstimatedCredits($bot, ?string $messageContent, ?UploadedFile $mediaFile): float
    {
        if (!$bot) {
            return 0.0;
        }

        try {
            // Get AI services to determine provider and model
            $services = $this->getAIServices();
            $chatService = $services['chat'];
            $embeddingService = $services['embedding'];
            
            $provider = $chatService->getProvider();
            $model = $chatService->getModel();
            $embeddingModel = $embeddingService->getEmbeddingModel();
            
            // Estimate tokens for different components
            $totalEstimatedCredits = 0.0;
            
            // 1. Estimate embedding tokens (for knowledge search)
            if ($messageContent) {
                $embeddingTokens = $this->estimateTokens($messageContent);
                $embeddingCredits = $this->calculateCreditsForTokens(
                    $this->tokenPricingService,
                    $provider, 
                    $embeddingModel, 
                    $embeddingTokens, 
                    0
                );
                $totalEstimatedCredits += $embeddingCredits;
            }
            
            // 2. Estimate main chat tokens
            $systemPrompt = $this->buildSystemPrompt($bot, null, true);
            $fullPrompt = $systemPrompt;
            
            if ($messageContent) {
                $fullPrompt .= "\n\nUser: " . $messageContent;
            }
            
            // Add media processing overhead if media file exists
            if ($mediaFile) {
                $mimeType = $mediaFile->getMimeType();
                
                // Add transcription cost for audio files
                if ($this->isAudioMimeType($mimeType)) {
                    $estimatedTranscriptionTokens = $this->getTranscriptionTokenEstimate();
                    $transcriptionCredits = $this->calculateCreditsForTokens(
                        $this->tokenPricingService,
                        $provider, 
                        $model, 
                        $estimatedTranscriptionTokens, 
                        0
                    );
                    $totalEstimatedCredits += $transcriptionCredits;
                }
                
                // Add vision processing overhead for images/videos
                if ($this->requiresVisionProcessing($mimeType)) {
                    $fullPrompt .= "\n\n[Image/Video content analysis]";
                }
            }
            
            // Estimate input and output tokens for main chat
            $inputTokens = $this->estimateTokens($fullPrompt);
            $outputTokens = $this->getResponseTokenEstimate();
            
            $chatCredits = $this->calculateCreditsForTokens(
                $this->tokenPricingService,
                $provider, 
                $model, 
                $inputTokens, 
                $outputTokens
            );
            
            $totalEstimatedCredits += $chatCredits;
            
            // 3. Add buffer for tool calls (if any tools are available)
            $tools = $bot->team->tools()->where('is_active', true)->count();
            $totalEstimatedCredits = $this->applyToolCallBuffer($totalEstimatedCredits, $tools);
            
            // Add safety margin
            $totalEstimatedCredits = $this->applySafetyMargin($totalEstimatedCredits);
            
            return round($totalEstimatedCredits, 6);
        } catch (\Exception $e) {
            // Fallback to conservative estimate if calculation fails
            $baseTokens = $messageContent ? $this->estimateTokens($messageContent) : 100;
            $fallbackCredits = ($baseTokens / 1000000) * 50000; // Conservative fallback
            return round($fallbackCredits, 6);
        }
    }
    
}
