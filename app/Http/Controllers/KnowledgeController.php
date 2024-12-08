<?php

namespace App\Http\Controllers;

use App\Models\Knowledge;
use App\Models\Bot;
use App\Services\KnowledgeService;
use App\Jobs\IndexKnowledgeJob;
use Illuminate\Http\Request;

class KnowledgeController extends Controller
{
    protected $knowledgeService;

    public function __construct(KnowledgeService $knowledgeService)
    {
        $this->authorizeResource(Knowledge::class);
        $this->knowledgeService = $knowledgeService;
    }

    public function index(Request $request)
    {
        $knowledges = $request->user()->currentTeam->knowledges()
            ->select('id', 'name', 'type', 'status', 'created_at')
            ->latest()
            ->get();

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
            'status' => 'pending'
        ]);

        // Dispatch the indexing job
        IndexKnowledgeJob::dispatch($knowledge);

        // If bot_id is provided, connect the knowledge to the bot
        if ($request->has('bot_id') && $bot = Bot::find($request->bot_id)) {
            $this->authorize('update', $bot);
            $bot->knowledge()->attach($knowledge->id);
            return redirect()->route('bots.show', $bot)->with('success', 'Knowledge created and indexing started.');
        }

        return redirect()->route('knowledges.index')->with('success', 'Knowledge created and indexing started.');
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
            'status' => 'pending'
        ]);

        // Dispatch the indexing job
        IndexKnowledgeJob::dispatch($knowledge);

        return redirect()->route('knowledges.index')->with('success', 'Knowledge updated and re-indexing started.');
    }

    public function destroy(Knowledge $knowledge)
    {
        $knowledge->delete();

        return redirect()->route('knowledges.index')->with('success', 'Knowledge deleted successfully.');
    }
}
