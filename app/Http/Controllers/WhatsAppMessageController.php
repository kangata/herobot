<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use App\Models\Channel;
use App\Models\Transaction;
use App\Services\AIResponseService;
use Illuminate\Http\Request;

class WhatsAppMessageController extends Controller
{
    protected $aiResponseService;

    public function __construct(AIResponseService $aiResponseService)
    {
        $this->aiResponseService = $aiResponseService;
    }

    public function handleIncomingMessage(Request $request)
    {
        $channelId = $request->input('channelId');
        $sender = $request->input('sender');
        $messageContent = $request->input('message', '');
        $messageType = $request->input('messageType', 'text');
        $media = $request->input('media');

        if (empty($messageContent) && $media) {
            if($messageType == 'image') {
                $messageContent = 'Tolong Jelaskan media yang saya kirimkan.';
            }
            else if($messageType == 'audio') {
                $messageContent = 'balas audio ini';
            }
        }

        $channel = Channel::with(['bots', 'team.balance'])->findOrFail($channelId);
        $bot = $channel->bots->first();
        $team = $channel->team;

        if (! $bot) {
            return response()->json(['error' => 'No bot found for this channel'], 404);
        }

        // // Check if team has enough credits (150 per response)
        // if ($team->balance->amount < 150) {
        //     return response()->json(['error' => 'Insufficient credits. Please top up your credits to continue using the service.'], 402);
        // }

        // Get latest transaction
        $latestTransaction = Transaction::where('team_id', $team->id)
            ->latest()
            ->first();

        $chatHistory = ChatHistory::where('channel_id', $channelId)
            ->where('sender', $sender)
            ->latest()
            ->take(5)
            ->get()
            ->reverse()
            ->values();

        $response = $this->aiResponseService->generateResponse($bot, $messageContent, $chatHistory, $media, $messageType);

        // Create or update transaction record
        if ($latestTransaction && $latestTransaction->type == 'usage' && $latestTransaction->created_at->isToday()) {
            // Update existing transaction for today
            $totalResponses = ($latestTransaction->amount / 150) + 1;
            $latestTransaction->update([
                'amount' => $latestTransaction->amount + 150,
                'description' => 'AI Response Credits Usage (Total responses: ' . $totalResponses . ')',
            ]);
        } else {
            // Create new transaction
            Transaction::create([
                'team_id' => $team->id,
                'amount' => 150,
                'type' => 'usage',
                'description' => 'AI Response Credits Usage (Total responses: 1)',
                'status' => 'completed',
            ]);
        }

        // Deduct credits from team's balance
        $team->balance->decrement('amount', 150);

        $this->saveChatHistory($channelId, $sender, $messageContent, $response);

        return response()->json(['response' => $response]);
    }

    private function saveChatHistory($channelId, $sender, $message, $response)
    {
        ChatHistory::create([
            'channel_id' => $channelId,
            'sender' => $sender,
            'message' => $message,
            'response' => $response,
        ]);
    }
}
