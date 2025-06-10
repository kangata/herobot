<?php

namespace App\Http\Controllers;

use App\Facades\WhatsApp;
use App\Models\Bot;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ChannelController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Channel::class);
    }

    public function index(Request $request)
    {
        $channels = $request->user()->channels()->all();

        return inertia('Channels/Index', [
            'channels' => $channels,
        ]);
    }

    public function create(Request $request)
    {
        return inertia('Channels/Create', [
            'bot_id' => $request->query('bot_id'),
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'type' => 'required|in:whatsapp',
        ]);

        $channel = Channel::create([
            'team_id' => $request->user()->currentTeam->id,
            'name' => $validatedData['name'],
            'type' => $validatedData['type'],
        ]);

        // If bot_id is provided, connect the channel to the bot
        if ($request->has('bot_id') && $bot = Bot::find($request->bot_id)) {
            $this->authorize('update', $bot);
            $bot->channels()->attach($channel->id);

            return redirect()->route('bots.show', $bot)->with('success', 'Channel created and connected successfully.');
        }

        return redirect()->route('channels.show', $channel)->with('success', 'Channel created successfully.');
    }

    public function show(Channel $channel)
    {
        return inertia('Channels/Show', [
            'channel' => $channel,
            'whatsapp' => Inertia::lazy(
                fn () => WhatsApp::status($channel->id) ?? null
            ),
        ]);
    }

    public function edit(Channel $channel)
    {
        return inertia('Channels/Edit', [
            'channel' => $channel,
        ]);
    }

    public function update(Request $request, Channel $channel)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'type' => 'required|in:whatsapp',
        ]);

        $channel->update([
            'name' => $validatedData['name'],
            'type' => $validatedData['type'],
        ]);

        return redirect()->route('channels.show', $channel)->with('success', 'Channel updated successfully.');
    }

    public function disconnect(Channel $channel)
    {
        $result = WhatsApp::disconnect($channel->id);

        if ($result['success']) {
            $channel->update(['is_connected' => false, 'phone' => null]);

            return redirect()->route('channels.show', $channel)->with('success', 'WhatsApp disconnected successfully.');
        }

        return redirect()->route('channels.show', $channel)->with('error', 'Failed to disconnect WhatsApp.');
    }

    public function destroy(Request $request, Channel $channel)
    {
        if ($channel->type === 'whatsapp') {
            dispatch(function () use ($channel) {
                WhatsApp::disconnect($channel->id);
            });
        }

        DB::transaction(function () use ($channel) {
            $channel->bots()->detach();
            $channel->chatHistories()->delete();
            $channel->delete();
        });

        return redirect()->route('channels.index')->with('success', 'Channel deleted successfully.');
    }
}
