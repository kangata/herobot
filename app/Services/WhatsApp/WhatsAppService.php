<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    private string $baseUrl;

    public function __construct(string $baseUrl = 'http://localhost:3000')
    {
        $this->baseUrl = $baseUrl;
    }

    public function connect(string $channelId): array
    {
        $response = Http::post("{$this->baseUrl}/connect", [
            'channelId' => $channelId,
        ]);

        return $response->json();
    }

    public function status(string $channelId): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/status/{$channelId}");
        } catch (\Exception $e) {
            return [];
        }

        return $response->json();
    }

    public function sendMessage(string $channelId, string $recipient, string $message): array
    {
        $response = Http::post("{$this->baseUrl}/send-message", [
            'channelId' => $channelId,
            'recipient' => $recipient,
            'message' => $message,
        ]);

        return $response->json();
    }

    public function disconnect(string $channelId): array
    {
        $response = Http::post("{$this->baseUrl}/disconnect", [
            'channelId' => $channelId,
        ]);

        return $response->json();
    }
}
