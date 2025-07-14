<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Knowledge;
use App\Services\AIServiceFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BotController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Bot::class);
    }

    public function index(Request $request)
    {
        $bots = Bot::with('channels')
            ->where('team_id', $request->user()->currentTeam->id)
            ->get();

        return inertia('Bots/Index', [
            'bots' => $bots,
        ]);
    }

    public function create()
    {
        return inertia('Bots/Create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'prompt' => 'required|string',
        ]);

        $bot = Bot::create([
            'team_id' => $request->user()->currentTeam->id,
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'prompt' => $validatedData['prompt'],
        ]);

        return redirect()->route('bots.show', $bot)->with('success', 'Bot created successfully.');
    }

    public function show(Bot $bot)
    {
        $bot->load('channels', 'knowledge');

        $availableChannels = Channel::where('team_id', $bot->team_id)
            ->whereNotIn('id', $bot->channels->pluck('id'))
            ->get();

        $availableKnowledge = Knowledge::where('team_id', $bot->team_id)
            ->whereNotIn('id', $bot->knowledge->pluck('id'))
            ->get();

        return inertia('Bots/Show', [
            'bot' => $bot,
            'availableChannels' => $availableChannels,
            'availableKnowledge' => $availableKnowledge,
        ]);
    }

    public function edit(Bot $bot)
    {
        return inertia('Bots/Edit', [
            'bot' => $bot,
        ]);
    }

    public function update(Request $request, Bot $bot)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'prompt' => 'required|string',
        ]);

        $bot->update([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'prompt' => $validatedData['prompt'],
        ]);

        return redirect()->route('bots.show', $bot)->with('success', 'Bot updated successfully.');
    }

    public function destroy(Bot $bot)
    {
        DB::transaction(function () use ($bot) {
            $bot->channels()->detach();
            $bot->knowledge()->detach();
            $bot->delete();
        });

        return redirect()->route('bots.index')->with('success', 'Bot deleted successfully.');
    }

    public function connectChannel(Request $request, Bot $bot)
    {
        $validated = $request->validate([
            'channel_id' => 'required|exists:channels,id',
        ]);

        $bot->channels()->attach($validated['channel_id']);

        return back()->with('success', 'Channel connected successfully.');
    }

    public function disconnectChannel(Request $request, Bot $bot)
    {
        $validated = $request->validate([
            'channel_id' => 'required|exists:channels,id',
        ]);

        $bot->channels()->detach($validated['channel_id']);

        return back()->with('success', 'Channel disconnected successfully.');
    }

    public function connectKnowledge(Request $request, Bot $bot)
    {
        $validated = $request->validate([
            'knowledge_id' => 'required|exists:knowledge,id',
        ]);

        $bot->knowledge()->attach($validated['knowledge_id']);

        return back()->with('success', 'Knowledge connected successfully.');
    }

    public function disconnectKnowledge(Request $request, Bot $bot)
    {
        $validated = $request->validate([
            'knowledge_id' => 'required|exists:knowledge,id',
        ]);

        $bot->knowledge()->detach($validated['knowledge_id']);

        return back()->with('success', 'Knowledge disconnected successfully.');
    }

    public function testMessage(Request $request, Bot $bot)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'chat_history' => 'array',
            'chat_history.*.message' => 'required|string',
            'chat_history.*.response' => 'required|string',
        ]);

        try {
            $response = $this->generateTestResponse(
                $bot,
                $validated['message'],
                collect($validated['chat_history'] ?? [])
            );

            // For debugging, let's also log the response
            Log::info('Test response generated successfully', [
                'bot_id' => $bot->id,
                'message' => $validated['message'],
                'response' => $response
            ]);

            // Return back with flash data
            return back()->with('chatResponse', [
                'success' => true,
                'response' => $response,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate test response: ' . $e->getMessage(), [
                'bot_id' => $bot->id,
                'message' => $validated['message'],
                'exception' => $e->getTraceAsString()
            ]);

            return back()->with('chatResponse', [
                'success' => false,
                'error' => 'Failed to generate response. Please try again.',
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    private function generateTestResponse($bot, $message, $chatHistory)
    {
        // Get separately configured services
        $chatService = AIServiceFactory::createChatService();
        $embeddingService = AIServiceFactory::createEmbeddingService();

        Log::info('Using AI services for test', [
            'chat_service' => get_class($chatService),
            'embedding_service' => get_class($embeddingService),
        ]);

        // Search for relevant knowledge using embedding service
        $relevantKnowledge = $embeddingService->searchSimilarKnowledge($message, $bot, 3);

        Log::info('Relevant Knowledge found for test:', [
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
                        ['role' => 'user', 'content' => $ch['message']],
                        ['role' => 'assistant', 'content' => $ch['response']],
                    ];
                })
                ->flatten(1)
                ->toArray(),
            ['role' => 'user', 'content' => $message],
        ];

        // Generate response using chat service
        $response = $chatService->generateResponse($messages);

        return $this->convertMarkdownToWhatsApp($response);
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
}
