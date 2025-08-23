<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing transactions to set proper transaction_type
        DB::table('transactions')
            ->whereIn('type', ['usage', 'token_usage', 'refund'])
            ->update(['transaction_type' => 'debit']);
            
        DB::table('transactions')
            ->whereIn('type', ['topup', 'bonus', 'adjustment'])
            ->update(['transaction_type' => 'credit']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
