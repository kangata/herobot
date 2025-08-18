<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Knowledge;
use App\Models\Tool;
use App\Services\AIResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BotController extends Controller
{
    protected $aiResponseService;

    public function __construct(AIResponseService $aiResponseService)
    {
        $this->authorizeResource(Bot::class);

        $this->aiResponseService = $aiResponseService;
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

        return inertia('Bots/Show', [
            'bot' => $bot,
            'availableChannels' => $availableChannels,
            'availableKnowledge' => $availableKnowledge,
            'availableTools' => $availableTools,
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
            'message' => 'required|string|max:1000',
            'chat_history' => 'array',
            'chat_history.*.message' => 'required|string',
            'chat_history.*.response' => 'required|string',
        ]);

        try {
            $response = $this->aiResponseService->generateResponse(
                $bot,
                $validated['message'],
                'testing',
                null, // no channel for testing
                null,
                'html'
            );

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
}
