<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $botToken;

    protected $chatId;

    protected $apiBaseUrl = 'https://api.telegram.org/bot';

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
    }

    public function sendMessage(string $message): bool
    {
        try {
            $response = Http::post($this->apiBaseUrl.$this->botToken.'/sendMessage', [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if (! $response->successful()) {
                Log::error('Failed to send Telegram notification', [
                    'error' => $response->body(),
                    'status' => $response->status(),
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending Telegram notification', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
