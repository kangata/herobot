<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Models\ChatHistory;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppMessageController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

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
        $siteUrl = config('app.url');
        $siteName = config('app.name');
        [
            'api_key' => $apiKey,
            'model' => $model
        ] = config('services.openrouter');

        // Find the most relevant knowledge using vector similarity
        $relevantKnowledge = $this->openAIService->searchSimilarKnowledge($message, $bot, 3);

        $systemPrompt = "You are a helpful assistant for {$bot->name}. ";
        
        if ($relevantKnowledge->isNotEmpty()) {
            $systemPrompt .= "Use the following relevant knowledge to answer the user's question. If the knowledge doesn't help answer the question, use your general knowledge:\n\n";
            foreach ($relevantKnowledge as $knowledge) {
                $systemPrompt .= "--- {$knowledge['knowledge_name']} ---\n{$knowledge['text']}\n\n";
            }
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
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
                'Authorization' => "Bearer {$apiKey}",
                'HTTP-Referer' => $siteUrl,
                'X-Title' => $siteName,
                'Content-Type' => 'application/json'
            ])->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages
            ]);

            $response = $response->json()['choices'][0]['message']['content'];

            Log::info('OpenRouter: ' . json_encode([
                'model' => $model,
                'messages' => $messages,
                'response' => $response,
            ]));

            // Convert markdown to WhatsApp formatting
            $response = $this->convertMarkdownToWhatsApp($response);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to generate response: ' . $e->getMessage());
            return "I'm sorry, I couldn't generate a response at the moment. Please try again later.";
        }
    }

    private function convertMarkdownToWhatsApp($text)
    {
        // Convert italic: *text* or _text_ to _text_
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)|_(.+?)_/', '_$1$2_', $text);

        // Convert bold: **text** or __text__ to *text*
        $text = preg_replace('/(\*\*|__)(.*?)\1/', '*$2*', $text);

        // Convert strikethrough: ~~text~~ to ~text~
        $text = preg_replace('/~~(.*?)~~/', '~$1~', $text);

        // Convert inline code: `text` to ```text```
        $text = preg_replace('/`([^`]+)`/', '```$1```', $text);

        // Convert bullet points: - text to • text
        $text = preg_replace('/^- /m', '• ', $text);

        // Convert links: [text](url) to text: url
        $text = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '$2', $text);

        // Convert headers: # text to text
        $text = preg_replace('/^#+\s+(.*)$/m', '*$1*', $text);

        return $text;
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