<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\ChatHistory;
use App\Models\Knowledge;
use App\Models\Tool;
use App\Services\MessageHandlerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BotController extends Controller
{
    protected $messageHandlerService;

    public function __construct(MessageHandlerService $messageHandlerService)
    {
        $this->authorizeResource(Bot::class);

        $this->messageHandlerService = $messageHandlerService;
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
        $bot->load('channels', 'knowledge', 'tools');

        $availableChannels = Channel::where('team_id', $bot->team_id)
            ->whereNotIn('id', $bot->channels->pluck('id'))
            ->get();

        $availableKnowledge = Knowledge::where('team_id', $bot->team_id)
            ->whereNotIn('id', $bot->knowledge->pluck('id'))
            ->get();

        $availableTools = Tool::where('team_id', $bot->team_id)
            ->whereNotIn('id', $bot->tools->pluck('id'))
            ->get();

        $chatHistories = ChatHistory::where('bot_id', $bot->id)
            ->where('sender', 'testing')
            ->orderBy('created_at', 'desc')
            ->get();

        return inertia('Bots/Show', [
            'bot' => $bot,
            'availableChannels' => $availableChannels,
            'availableKnowledge' => $availableKnowledge,
            'availableTools' => $availableTools,
            'chatHistories' => $chatHistories,
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

    public function connectTool(Request $request, Bot $bot)
    {
        $validated = $request->validate([
            'tool_id' => 'required|exists:tools,id',
        ]);

        $bot->tools()->attach($validated['tool_id']);

        return back()->with('success', 'Tool connected successfully.');
    }

    public function disconnectTool(Request $request, Bot $bot)
    {
        $validated = $request->validate([
            'tool_id' => 'required|exists:tools,id',
        ]);

        $bot->tools()->detach($validated['tool_id']);

        return back()->with('success', 'Tool disconnected successfully.');
    }

    public function testMessage(Request $request, Bot $bot)
    {
        $validated = $request->validate([
            'message' => 'nullable|string|max:1000',
            'media_file' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,gif,webp,mp3,wav,ogg,m4a,webm,flac,mp4,avi,mov,pdf,doc,docx,txt',
        ]);

        try {
            $messageContent = $validated['message'] ?? null;
            $mediaFile = $request->hasFile('media_file') ? $request->file('media_file') : null;

            // Use handleMessage method with bot parameter for testing
            $result = $this->messageHandlerService->handleMessage(
                null, // no channel for testing
                'testing',
                $messageContent,
                $mediaFile,
                $bot,
            );

            $response = $result['response'];
            $media = $result['media'];

            // Return back with flash data
            return back()->with('chatResponse', [
                'success' => true,
                'response' => $response,
                'timestamp' => now()->toISOString(),
                'hasMedia' => $media !== null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate test response: ' . $e->getMessage(), [
                'bot_id' => $bot->id,
                'message' => $validated['message'] ?? null,
                'has_media' => $request->hasFile('media_file'),
                'exception' => $e->getTraceAsString()
            ]);

            return back()->with('chatResponse', [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    public function clearChat(Bot $bot)
    {
        try {
            // Delete all chat histories for this bot with sender 'testing'
            ChatHistory::where('bot_id', $bot->id)
                ->where('sender', 'testing')
                ->delete();

            return back()->with('success', 'Chat history cleared successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to clear chat history: ' . $e->getMessage(), [
                'bot_id' => $bot->id,
                'exception' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to clear chat history. Please try again.');
        }
    }
}
