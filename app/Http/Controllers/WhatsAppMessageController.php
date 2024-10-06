<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppMessageController extends Controller
{
    public function handleIncomingMessage(Request $request)
    {
        $integrationId = $request->input('integrationId');
        $sender = $request->input('sender');
        $messageContent = $request->input('message');

        $integration = Integration::with(['bots.knowledge'])->findOrFail($integrationId);
        $bot = $integration->bots->first();

        if (!$bot) {
            return response()->json(['error' => 'No bot found for this integration'], 404);
        }

        $chatHistory = ChatHistory::where('integration_id', $integrationId)
            ->where('sender', $sender)
            ->latest()
            ->take(5)
            ->get()
            ->reverse()
            ->values();

        $response = $this->generateResponse($bot, $messageContent, $chatHistory);

        $this->saveChatHistory($integrationId, $sender, $messageContent, $response);

        return response()->json(['response' => $response]);
    }

    private function generateResponse($bot, $message, $chatHistory)
    {
        $openrouterApiKey = config('services.openrouter.api_key');
        $siteUrl = config('app.url');
        $siteName = config('app.name');

        $messages = [
            ['role' => 'system', 'content' => "You are a helpful assistant for {$bot->name}. Use the following knowledge to answer questions: " . json_encode($bot->knowledge->pluck('text'))],
            ...$chatHistory->map(function ($ch) {
                return [
                    ['role' => 'user', 'content' => $ch->message],
                    ['role' => 'assistant', 'content' => $ch->response]
                ];
            })->flatten(1)->toArray(),
            ['role' => 'user', 'content' => $message]
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$openrouterApiKey}",
                'HTTP-Referer' => $siteUrl,
                'X-Title' => $siteName,
                'Content-Type' => 'application/json'
            ])->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'google/gemini-flash-1.5-exp',
                'messages' => $messages
            ]);

            return $response->json()['choices'][0]['message']['content'];
        } catch (\Exception $e) {
            Log::error('Failed to generate response: ' . $e->getMessage());
            return "I'm sorry, I couldn't generate a response at the moment. Please try again later.";
        }
    }

    private function saveChatHistory($integrationId, $sender, $message, $response)
    {
        ChatHistory::create([
            'integration_id' => $integrationId,
            'sender' => $sender,
            'message' => $message,
            'response' => $response
        ]);
    }
}