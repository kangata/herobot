<?php

namespace App\Services;

use App\Models\Balance;
use App\Models\TokenUsage;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TokenUsageService
{
    /**
     * Create a token usage record and update daily transaction.
     */
    public function createTokenUsage(array $data): TokenUsage
    {
        return DB::transaction(function () use ($data) {
            // Create the token usage record
            $tokenUsage = TokenUsage::create($data);
            
            // Update or create daily transaction
            $this->updateDailyTransaction($data['team_id'], $data['credits']);
            
            return $tokenUsage;
        });
    }
    
    /**
     * Update or create daily transaction for token usage.
     */
    private function updateDailyTransaction(int $teamId, float $credits): void
    {
        $today = Carbon::today();
        
        // Find existing transaction for today
        $transaction = Transaction::where('team_id', $teamId)
            ->where('type', 'AI usage')
            ->whereDate('created_at', $today)
            ->first();
            
        if ($transaction) {
            // Update existing transaction - amount is stored as integer
            $currentDecimalAmount = $transaction->decimal_amount;
            $transaction->amount = $currentDecimalAmount + $credits;
            $transaction->update([
                'description' => 'AI usage credits - ' . $today->format('Y-m-d'),
            ]);
        } else {
            // Create new daily transaction
            Transaction::create([
                'team_id' => $teamId,
                'amount' => $credits,
                'type' => 'AI usage',
                'transaction_type' => 'debit',
                'description' => 'AI usage credits - ' . $today->format('Y-m-d'),
                'status' => 'completed',
                'payment_method' => 'credits',
            ]);
        }
        
        // Update team balance after transaction
        $this->updateTeamBalance($teamId, $credits);
    }
    
    /**
     * Update team balance by deducting credits.
     */
    private function updateTeamBalance(int $teamId, float $credits): void
    {
        $balance = Balance::firstOrCreate(
            ['team_id' => $teamId],
            ['amount' => 0]
        );
        
        // Amount is stored as integer, use decimal_amount for calculation
        $currentDecimalAmount = $balance->decimal_amount;
        $balance->amount = $currentDecimalAmount - $credits;
        $balance->save();
    }
}
