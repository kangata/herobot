<?php

namespace App\Services;

use App\Models\ChatMedia;
use App\Services\AIServiceFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class MediaProcessingService
{
    /**
     * Process an uploaded media file.
     *
     * @param \Illuminate\Http\UploadedFile $mediaFile
     * @param string|null &$messageContent
     * @return \App\Models\ChatMedia|null
     */
    public function process(UploadedFile $mediaFile, ?string &$messageContent): ?ChatMedia
    {
        $media = null;
        $mimeType = $mediaFile->getMimeType();

        try {
            $fileContent = file_get_contents($mediaFile->getPathname());
            $base64Data = base64_encode($fileContent);
            $media = new ChatMedia($base64Data, $mimeType);

            // Synthesize a default prompt if the message is empty
            if (is_null($messageContent) || trim($messageContent) === '') {
                $messageContent = $this->generateDefaultPrompt($mimeType);
            }

            // Transcribe audio and append to the message
            if (str_starts_with($mimeType, 'audio/')) {
                $transcription = $this->transcribeAudio($base64Data, $mimeType);
                if (!empty($transcription)) {
                    $messageContent = rtrim((string) $messageContent) . "\n\n[Audio transcription: " . $transcription . ']';
                    $media = null; // Don't store the audio if transcribed
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to process media file', ['error' => $e->getMessage()]);
            // Return null on failure
            return null;
        }

        return $media;
    }

    /**
     * Generate a default prompt based on the media's MIME type.
     *
     * @param string $mimeType
     * @return string
     */
    private function generateDefaultPrompt(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'Please respond based on the attached image.';
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return 'Please respond based on the attached audio.';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'Please respond based on the attached video.';
        }

        return 'Please respond based on the attached document.';
    }

    /**
     * Transcribe audio data using the configured speech-to-text service.
     *
     * @param string $base64Data
     * @param string $mimeType
     * @return string|null
     */
    private function transcribeAudio(string $base64Data, string $mimeType): ?string
    {
        try {
            $speechToTextService = AIServiceFactory::createSpeechToTextService();
            return $speechToTextService->transcribe($base64Data, $mimeType);
        } catch (\Exception $e) {
            Log::warning('Failed to transcribe audio', ['error' => $e->getMessage()]);
            return null;
        }
    }
}