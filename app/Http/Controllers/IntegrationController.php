<?php

namespace App\Http\Controllers;

use App\Facades\WhatsApp;
use App\Models\Integration;
use App\Events\QrCodeUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Broadcast;
use Inertia\Inertia;

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
            'qr' => Inertia::lazy(fn () => WhatsApp::getQR($integration->id)['data']),
        ]);
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
