<?php

namespace App\Http\Controllers;

use App\Facades\WhatsApp;
use App\Models\Integration;
use App\Events\QrCodeUpdated;
use App\Models\Bot;
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

    public function create(Request $request)
    {
        return inertia('Integrations/Create', [
            'bot_id' => $request->query('bot_id'),
        ]);
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

        // If bot_id is provided, connect the integration to the bot
        if ($request->has('bot_id') && $bot = Bot::find($request->bot_id)) {
            $this->authorize('update', $bot);
            $bot->integrations()->attach($integration->id);
            return redirect()->route('bots.show', $bot)->with('success', 'Integration created and connected successfully.');
        }

        return redirect()->route('integrations.show', $integration)->with('success', 'Integration created successfully.');
    }

    public function show(Integration $integration)
    {
        return inertia('Integrations/Show', [
            'integration' => $integration,
            'whatsapp' => Inertia::lazy(
                fn () => WhatsApp::status($integration->id) ?? null
            ),
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

    public function disconnect(Integration $integration)
    {
        $result = WhatsApp::disconnect($integration->id);

        if ($result['success']) {
            $integration->update(['is_connected' => false, 'phone' => null]);
            return redirect()->route('integrations.show', $integration)->with('success', 'WhatsApp disconnected successfully.');
        }

        return redirect()->route('integrations.show', $integration)->with('error', 'Failed to disconnect WhatsApp.');
    }

    public function destroy(Integration $integration)
    {
        if ($integration->type === 'whatsapp') {
            dispatch(function () use ($integration) {
                WhatsApp::disconnect($integration->id);
            });
        }

        $integration->delete();

        return redirect()->route('integrations.index')->with('success', 'Integration deleted successfully.');
    }
}
