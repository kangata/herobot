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
        Schema::table('chat_histories', function (Blueprint $table) {
            // Make channel_id nullable for testing scenarios
            $table->unsignedBigInteger('channel_id')->nullable()->change();
            
            // Remove response column since we'll use message for both roles
            $table->dropColumn('response');
            
            // Add new columns for comprehensive chat history
            $table->string('role')->default('user'); // user, assistant, system, tool
            $table->json('media_data')->nullable(); // Store media information
            $table->json('tool_calls')->nullable(); // Store tool calls made by AI
            $table->json('metadata')->nullable(); // Additional metadata (format, model used, etc.)
            $table->string('message_type')->default('text'); // text, media, tool_call, tool_response
            $table->foreignId('bot_id')->nullable()->constrained()->onDelete('cascade');
            $table->text('raw_content')->nullable(); // Store raw AI response before formatting
            $table->string('tool_call_id')->nullable(); // Store tool call ID for tool role messages
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_histories', function (Blueprint $table) {
            $table->dropForeign(['bot_id']);
            $table->dropColumn([
                'role',
                'media_data',
                'tool_calls',
                'metadata',
                'message_type',
                'bot_id',
                'raw_content',
                'tool_call_id'
            ]);
            
            // Revert channel_id back to not nullable
            $table->unsignedBigInteger('channel_id')->nullable(false)->change();
            
            // Add back response column
            $table->text('response')->after('message');
        });
    }
};
