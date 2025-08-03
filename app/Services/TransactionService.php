<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Transaction;

class TransactionService
{
    /**
     * The cost for a single AI response.
     *
     * @var int
     */
    protected $responseCost;

    public function __construct()
    {
        $this->responseCost = config('herobot.response_cost', 150);
    }

    /**
     * Process a new AI response usage transaction for a team.
     *
     * @param \App\Models\Team $team
     * @return void
     */
    public function recordUsage(Team $team): void
    {
        // Find the latest usage transaction for today
        $latestTransaction = Transaction::where('team_id', $team->id)
            ->where('type', 'usage')
            ->whereDate('created_at', today())
            ->latest()
            ->first();

        if ($latestTransaction) {
            // Update existing transaction for today
            $totalResponses = ($latestTransaction->amount / $this->responseCost) + 1;
            $latestTransaction->update([
                'amount' => $latestTransaction->amount + $this->responseCost,
                'description' => 'AI Response Credits Usage (Total responses: ' . $totalResponses . ')',
            ]);
        } else {
            // Create new transaction for today
            Transaction::create([
                'team_id' => $team->id,
                'amount' => $this->responseCost,
                'type' => 'usage',
                'description' => 'AI Response Credits Usage (Total responses: 1)',
                'status' => 'completed',
            ]);
        }

        // Deduct credits from the team's balance
        if ($team->balance) {
            $team->balance->decrement('amount', $this->responseCost);
        }
    }
}