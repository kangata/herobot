<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreToolRequest;
use App\Http\Requests\UpdateToolRequest;
use App\Models\Tool;
use App\Services\ToolService;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    public function __construct(protected ToolService $toolService)
    {
        $this->authorizeResource(Tool::class);
    }

    public function index(Request $request)
    {
        $tools = $request->user()->currentTeam->tools()
            ->latest()
            ->get();

        return inertia('Tools/Index', [
            'tools' => $tools,
        ]);
    }

    public function create()
    {
        $toolTypes = $this->toolService->getAvailableToolTypes();
        
        return inertia('Tools/Create', [
            'toolTypes' => $toolTypes,
        ]);
    }

    public function store(StoreToolRequest $request)
    {
        $tool = $request->user()->currentTeam->tools()->create(
            $request->validated()
        );

        return redirect()->route('tools.show', $tool)->with('success', 'Tool created successfully.');
    }

    public function show(Request $request, Tool $tool)
    {
        $executionsPerPage = $request->get('executions_per_page', 10);
        $executionsPage = $request->get('executions_page', 1);
        
        $executions = $tool->executions()
            ->latest()
            ->paginate($executionsPerPage, ['*'], 'executions_page', $executionsPage);
        
        return inertia('Tools/Show', [
            'tool' => $tool,
            'executions' => $executions,
        ]);
    }

    public function edit(Tool $tool)
    {
        $toolTypes = $this->toolService->getAvailableToolTypes();
        
        return inertia('Tools/Edit', [
            'tool' => $tool,
            'toolTypes' => $toolTypes,
        ]);
    }

    public function update(UpdateToolRequest $request, Tool $tool)
    {
        $tool->update($request->validated());

        return redirect()->route('tools.show', $tool)->with('success', 'Tool updated successfully.');
    }

    public function destroy(Tool $tool)
    {
        $tool->delete();

        return redirect()->route('tools.index')->with('success', 'Tool deleted successfully.');
    }

    public function types()
    {
        return response()->json([
            'data' => $this->toolService->getAvailableToolTypes(),
        ]);
    }

    public function execute(Request $request, Tool $tool)
    {
        $this->authorize('execute', $tool);

        $validated = $request->validate([
            'parameters' => 'array',
        ]);
        
        $parameters = $validated['parameters'] ?? [];

        $execution = $this->toolService->executeTool(
            $tool,
            $parameters,
            $request->input('chat_history_id')
        );

        return back()->with('success', 'Tool executed successfully.');
    }

    public function executions(Tool $tool)
    {
        $this->authorize('view', $tool);

        $executions = $tool->executions()
            ->latest()
            ->paginate(20);

        return inertia('Tools/Executions', [
            'tool' => $tool,
            'executions' => $executions,
        ]);
    }

    public function test(Request $request, Tool $tool)
    {
        $this->authorize('execute', $tool);

        $validated = $request->validate([
            'parameters' => 'array',
        ]);
        
        $parameters = $validated['parameters'] ?? [];

        $result = $this->toolService->testTool($tool, $parameters);

        return back()->with('testResult', $result);
    }

    public function validateConfiguration(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'params' => 'required|array',
            'parameters_schema' => 'required|array',
        ]);

        $errors = $this->toolService->validateToolConfiguration(
            $validated['type'],
            $validated['params'],
            $validated['parameters_schema']
        );

        return response()->json([
            'valid' => empty($errors),
            'errors' => $errors,
        ]);
    }
}
