<?php

namespace App\Http\Controllers;

use App\Models\Knowledge;
use Illuminate\Http\Request;

class KnowledgeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Knowledge::class);
    }

    public function index(Request $request)
    {
        $knowledges = $request->user()->knowledges()->all();

        return inertia('Knowledges/Index', [
            'knowledges' => $knowledges
        ]);
    }

    public function create()
    {
        return inertia('Knowledges/Create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'value' => 'required',
        ]);

        Knowledge::create([
            'team_id' => $request->user()->currentTeam->id,
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'data' => $validatedData['value'],
        ]);

        return redirect()->route('knowledges.index')->with('success', 'Knowledge created successfully.');
    }

    public function edit(Knowledge $knowledge)
    {
        return inertia('Knowledges/Edit', [
            'knowledge' => $knowledge,
        ]);
    }

    public function update(Request $request, Knowledge $knowledge)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'value' => 'required',
        ]);

        $knowledge->update([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'data' => $validatedData['value'],
        ]);

        return redirect()->route('knowledges.index')->with('success', 'Knowledge updated successfully.');
    }

    public function destroy(Knowledge $knowledge)
    {
        $knowledge->delete();

        return redirect()->route('knowledges.index')->with('success', 'Knowledge deleted successfully.');
    }
}
