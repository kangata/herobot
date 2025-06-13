<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use App\Models\Channel;
use App\Models\Transaction;
use App\Services\AIServiceFactory;
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
        $channelId = $request->input('channelId');
        $sender = $request->input('sender');
        $messageContent = $request->input('message', '');
        $messageType = $request->input('messageType', 'text');
        $media = $request->input('media');

        if (empty($messageContent) && $media) {
            if($messageType == 'image') {
                $messageContent = 'Tolong Jelaskan media yang saya kirimkan.';
            }
            else if($messageType == 'audio') {
                $messageContent = 'balas audio ini';
            }
        }

        $channel = Channel::with(['bots', 'team.balance'])->findOrFail($channelId);
        $bot = $channel->bots->first();
        $team = $channel->team;

        if (! $bot) {
            return response()->json(['error' => 'No bot found for this channel'], 404);
        }

        // // Check if team has enough credits (150 per response)
        // if ($team->balance->amount < 150) {
        //     return response()->json(['error' => 'Insufficient credits. Please top up your credits to continue using the service.'], 402);
        // }

        // Get latest transaction
        $latestTransaction = Transaction::where('team_id', $team->id)
            ->latest()
            ->first();

        $chatHistory = ChatHistory::where('channel_id', $channelId)
            ->where('sender', $sender)
            ->latest()
            ->take(5)
            ->get()
            ->reverse()
            ->values();

        $response = $this->generateResponse($bot, $messageContent, $chatHistory, $media, $messageType);

        // Create or update transaction record
        if ($latestTransaction && $latestTransaction->type == 'usage' && $latestTransaction->created_at->isToday()) {
            // Update existing transaction for today
            $totalResponses = ($latestTransaction->amount / 150) + 1;
            $latestTransaction->update([
                'amount' => $latestTransaction->amount + 150,
                'description' => 'AI Response Credits Usage (Total responses: ' . $totalResponses . ')',
            ]);
        } else {
            // Create new transaction
            Transaction::create([
                'team_id' => $team->id,
                'amount' => 150,
                'type' => 'usage',
                'description' => 'AI Response Credits Usage (Total responses: 1)',
                'status' => 'completed',
            ]);
        }

        // Deduct credits from team's balance
        $team->balance->decrement('amount', 150);

        $this->saveChatHistory($channelId, $sender, $messageContent, $response);

        return response()->json(['response' => $response]);
    }

    /**
     * Generate response using configured AI services.
     *
     * @param  object                                   $bot         Instance model bot (memiliki properti "prompt")
     * @param  string                                   $message     Pesan terbaru dari pengguna
     * @param  \Illuminate\Support\Collection           $chatHistory Koleksi objek riwayat obrolan (memiliki properti "message" dan "response")
     * @return string|bool  String berisi jawaban terformat, atau false kalau gagal
     */
    private function generateResponse($bot, $message, $chatHistory, $media = null, $messageType = 'text')
    {
        try {
            // Get separately configured services
            $chatService = AIServiceFactory::createChatService();
            $embeddingService = AIServiceFactory::createEmbeddingService();
            
            Log::info('Using AI services', [
                'chat_service' => get_class($chatService),
                'embedding_service' => get_class($embeddingService),
            ]);
            
            // Search for relevant knowledge using embedding service
            $relevantKnowledge = $embeddingService->searchSimilarKnowledge($message, $bot, 3);
            
            Log::info('Relevant Knowledge found:', [
                'knowledge_count' => $relevantKnowledge->count(),
                'message' => $message,
            ]);
            
            // Build system prompt
            $systemPrompt = $bot->prompt;
            if ($relevantKnowledge->isNotEmpty()) {
                $systemPrompt .= "\n\nGunakan informasi berikut untuk menjawab pertanyaan:\n\n";
                foreach ($relevantKnowledge as $knowledge) {
                    $systemPrompt .= "{$knowledge['text']}\n\n";
                }
            } else {
                $systemPrompt .= "\n\nTidak ada informasi spesifik yang ditemukan dalam basis pengetahuan.";
            }
            
            // Build messages array
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ...$chatHistory
                    ->map(function ($ch) {
                        return [
                            ['role' => 'user', 'content' => $ch->message],
                            ['role' => 'assistant', 'content' => $ch->response],
                        ];
                    })
                    ->flatten(1)
                    ->toArray(),
                ['role' => 'user', 'content' => $message],
            ];

            
            // Generate response using chat service
            $response = $chatService->generateResponse($messages, null, $media ? $media['data'] : null, $media ? $messageType : null);
            
            return $this->convertMarkdownToWhatsApp($response);
            
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

    private function saveChatHistory($channelId, $sender, $message, $response)
    {
        ChatHistory::create([
            'channel_id' => $channelId,
            'sender' => $sender,
            'message' => $message,
            'response' => $response,
        ]);
    }
}
