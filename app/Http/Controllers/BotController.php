<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bot;

class BotController extends Controller
{
    public function index(Request $request)
    {
        $bots = $request->user()->currentTeamBots()->all();

        return inertia('Bot/Index', [
            'bots' => $bots
        ]);
    }

    public function create()
    {
        return inertia('Bot/Create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'personality' => 'required',
        ]);

        $bot = new Bot;
        $bot->name = $validatedData['name'];
        $bot->description = $validatedData['description'];
        $bot->personality = $validatedData['personality'];
        $bot->save();

        return redirect()->route('bots.index')->with('success', 'Bot created successfully.');
    }

    public function edit(Bot $bot)
    {
        return inertia('Bot/Edit', [
            'bot' => $bot
        ]);
    }

    public function update(Request $request, Bot $bot)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'personality' => 'required',
        ]);

        $bot->name = $validatedData['name'];
        $bot->description = $validatedData['description'];
        $bot->personality = $validatedData['personality'];
        $bot->save();

        return redirect()->route('bots.index')->with('success', 'Bot updated successfully.');
    }

    public function destroy(Bot $bot)
    {
        $bot->delete();

        return redirect()->route('bots.index')->with('success', 'Bot deleted successfully.');
    }
}
