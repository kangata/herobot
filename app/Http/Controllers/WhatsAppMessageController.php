<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Models\ChatHistory;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;

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

        $integration = Integration::with(['bots', 'team.balance'])->findOrFail($integrationId);
        $bot = $integration->bots->first();
        $team = $integration->team;

        if (!$bot) {
            return response()->json(['error' => 'No bot found for this integration'], 404);
        }

        // // Check if team has enough credits (150 per response)
        // if ($team->balance->amount < 150) {
        //     return response()->json(['error' => 'Insufficient credits. Please top up your credits to continue using the service.'], 402);
        // }

        // Get latest transaction
        $latestTransaction = Transaction::where('team_id', $team->id)
            ->latest()
            ->first();

        $chatHistory = ChatHistory::where('integration_id', $integrationId)
            ->where('sender', $sender)
            ->latest()
            ->take(5)
            ->get()
            ->reverse()
            ->values();

        $response = $this->generateResponse($bot, $messageContent, $chatHistory);

        // Create or update transaction record
        if ($latestTransaction && $latestTransaction->type == 'usage' && $latestTransaction->created_at->isToday()) {
            // Update existing transaction for today
            $totalResponses = ($latestTransaction->amount / 150) + 1;
            $latestTransaction->update([
                'amount' => $latestTransaction->amount + 150,
                'description' => 'AI Response Credits Usage (Total responses: ' . $totalResponses . ')'
            ]);
        } else {
            // Create new transaction
            Transaction::create([
                'team_id' => $team->id,
                'amount' => 150,
                'type' => 'usage',
                'description' => 'AI Response Credits Usage (Total responses: 1)',
                'status' => 'completed'
            ]);
        }

        // Deduct credits from team's balance
        $team->balance->decrement('amount', 150);

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

        // Use bot's custom prompt or fallback to default
        $systemPrompt = $bot->prompt;

        if ($relevantKnowledge->isNotEmpty()) {
            $systemPrompt .= "\n\nGunakan informasi berikut untuk menjawab pertanyaan:\n\n";
            foreach ($relevantKnowledge as $knowledge) {
                $systemPrompt .= "{$knowledge['text']}\n\n";
            }
        } else {
            $systemPrompt .= "\n\nTidak ada informasi spesifik yang ditemukan dalam basis pengetahuan. Tawarkan untuk menghubungkan dengan staf yang dapat membantu lebih lanjut.";
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
            ])->json();

            Log::info('OpenRouter: ' . json_encode([
                'model' => $model,
                'messages' => $messages,
                'response' => $response,
            ]));

            $response = $response['choices'][0]['message']['content'];

            // Convert markdown to WhatsApp formatting
            $response = $this->convertMarkdownToWhatsApp($response);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to generate response: ' . $e->getMessage());

            return false;
        }
    }

    private function convertMarkdownToWhatsApp($text)
    {
        // Convert italic: *text* or _text_ to _text_
        $text = preg_replace('/(?<!\*)\*(?!\*)(\S+?)(?<!\*)\*(?!\*)|_(\S+?)_/', '_$1$2_', $text);

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