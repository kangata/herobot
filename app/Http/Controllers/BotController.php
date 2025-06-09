<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\Channel;
use App\Models\Knowledge;
use Illuminate\Http\Request;

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
        $bot->delete();

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
}
