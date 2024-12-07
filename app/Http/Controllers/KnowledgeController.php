<?php

namespace App\Http\Controllers;

use App\Models\Knowledge;
use App\Models\Bot;
use Illuminate\Http\Request;

class KnowledgeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Knowledge::class);
    }

    public function index(Request $request)
    {
        $knowledges = $request->user()->knowledges()->get();

        return inertia('Knowledges/Index', [
            'knowledges' => $knowledges
        ]);
    }

    public function create(Request $request)
    {
        return inertia('Knowledges/Form', [
            'bot_id' => $request->query('bot_id'),
        ]);
    }

    public function show(Knowledge $knowledge)
    {
        return redirect()->route('knowledges.edit', $knowledge);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:text'],
            'text' => ['required', 'string'],
        ]);

        $knowledge = Knowledge::create([
            'team_id' => $request->user()->currentTeam->id,
            'name' => $validatedData['name'],
            'type' => 'text',
            'text' => $validatedData['text'],
        ]);

        // If bot_id is provided, connect the knowledge to the bot
        if ($request->has('bot_id') && $bot = Bot::findOrFail($request->bot_id)) {
            $this->authorize('update', $bot);
            $bot->knowledge()->attach($knowledge->id);
            return redirect()->route('bots.show', $bot)->with('success', 'Knowledge created and connected successfully.');
        }

        return redirect()->route('knowledges.index')->with('success', 'Knowledge created successfully.');
    }

    public function edit(Knowledge $knowledge)
    {
        return inertia('Knowledges/Form', [
            'knowledge' => $knowledge,
        ]);
    }

    public function update(Request $request, Knowledge $knowledge)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:text'],
            'text' => ['required', 'string'],
        ]);

        $knowledge->update([
            'name' => $validatedData['name'],
            'type' => $validatedData['type'],
            'text' => $validatedData['text'],
        ]);

        return redirect()->route('knowledges.index')->with('success', 'Knowledge updated successfully.');
    }

    public function destroy(Knowledge $knowledge)
    {
        $knowledge->delete();

        return redirect()->route('knowledges.index')->with('success', 'Knowledge deleted successfully.');
    }
}
