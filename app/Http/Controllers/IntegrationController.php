<?php

namespace App\Http\Controllers;

use App\Facades\WhatsApp;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IntegrationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Integration::class);
    }

    public function index(Request $request)
    {
        $integrations = $request->user()->integrations()->all();

        return inertia('Integrations/Index', [
            'integrations' => $integrations
        ]);
    }

    public function create()
    {
        return inertia('Integrations/Create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'type' => 'required|in:whatsapp',
        ]);

        $integration = Integration::create([
            'team_id' => $request->user()->currentTeam->id,
            'name' => $validatedData['name'],
            'type' => $validatedData['type'],
        ]);

        return redirect()->route('integrations.show', $integration)->with('success', 'Integration created successfully.');
    }

    public function show(Integration $integration)
    {
        return inertia('Integrations/Show', [
            'integration' => $integration,
        ]);
    }

    public function getQR(Integration $integration)
    {
        $qrCode = WhatsApp::getQR($integration->id);

        return response()->json($qrCode);
    }

    public function edit(Integration $integration)
    {
        return inertia('Integrations/Edit', [
            'integration' => $integration,
        ]);
    }

    public function update(Request $request, Integration $integration)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'type' => 'required|in:whatsapp',
        ]);

        $integration->update([
            'name' => $validatedData['name'],
            'type' => $validatedData['type'],
        ]);

        return redirect()->route('integrations.show', $integration)->with('success', 'Integration updated successfully.');
    }

    public function destroy(Integration $integration)
    {
        $integration->delete();

        return redirect()->route('integrations.index')->with('success', 'Integration deleted successfully.');
    }
}
