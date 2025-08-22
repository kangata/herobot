<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('token_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('bot_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // 'openai' or 'gemini'
            $table->string('model'); // e.g., 'gpt-4o', 'gemini-2.5-flash'
            $table->integer('input_tokens');
            $table->integer('output_tokens');
            $table->decimal('tokens_per_second', 8, 2)->nullable(); // TPS calculation
            $table->decimal('credits', 12, 6); // Cost in credits (1 credit = 1 Rupiah) - higher precision for small costs
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['team_id', 'created_at']);
            $table->index(['bot_id', 'created_at']);
            $table->index(['provider', 'model']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_usages');
    }
};
