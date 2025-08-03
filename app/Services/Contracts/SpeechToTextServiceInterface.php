<?php

namespace App\Services\Contracts;

interface SpeechToTextServiceInterface
{
    /**
     * Convert speech audio to text
     *
     * @param string $audioData Base64 encoded audio data
     * @param string $mimeType Audio MIME type (e.g., 'audio/mp3', 'audio/wav', 'audio/ogg')
     * @param string|null $language Language code (e.g., 'en', 'id') - optional
     * @return string Transcribed text
     * @throws \Exception When transcription fails
     */
    public function transcribe(string $audioData, string $mimeType, ?string $language = null): string;
}
