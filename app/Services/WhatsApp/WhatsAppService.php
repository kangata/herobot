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

    public function connect(string $integrationId): array
    {
        $response = Http::post("{$this->baseUrl}/connect", [
            'integrationId' => $integrationId,
        ]);

        return $response->json();
    }

    public function status(string $integrationId): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/status/{$integrationId}");
        } catch (\Exception $e) {
            return [];
        }

        return $response->json();
    }

    public function sendMessage(string $integrationId, string $recipient, string $message): array
    {
        $response = Http::post("{$this->baseUrl}/send-message", [
            'integrationId' => $integrationId,
            'recipient' => $recipient,
            'message' => $message,
        ]);

        return $response->json();
    }

    public function disconnect(string $integrationId): array
    {
        $response = Http::post("{$this->baseUrl}/disconnect", [
            'integrationId' => $integrationId,
        ]);

        return $response->json();
    }
}