<?php

namespace App\Services;

use App\Models\ChatMedia;
use App\Services\Traits\AIServiceHelperTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class MediaProcessingService
{
    use AIServiceHelperTrait;

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
                $messageContent = $this->generateDefaultPromptForMimeType($mimeType);
            }

            // Transcribe audio and append to the message
            if ($this->isAudioMimeType($mimeType)) {
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
     * Transcribe audio data using the configured speech-to-text service.
     *
     * @param string $base64Data
     * @param string $mimeType
     * @return string|null
     */
    private function transcribeAudio(string $base64Data, string $mimeType): ?string
    {
        try {
            $speechToTextService = \App\Services\AIServiceFactory::createSpeechToTextService();
            return $speechToTextService->transcribe($base64Data, $mimeType);
        } catch (\Exception $e) {
            Log::warning('Failed to transcribe audio', ['error' => $e->getMessage()]);
            return null;
        }
    }
}