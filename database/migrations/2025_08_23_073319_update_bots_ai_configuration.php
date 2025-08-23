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
        Schema::table('bots', function (Blueprint $table) {
            // Add new flexible AI service configuration
            $table->string('ai_chat_service')->nullable()->after('prompt');
            $table->string('ai_embedding_service')->nullable()->after('ai_chat_service');
            $table->string('ai_speech_to_text_service')->nullable()->after('ai_embedding_service');
            
            // Add custom API keys
            $table->text('openai_api_key')->nullable()->after('ai_speech_to_text_service');
            $table->text('gemini_api_key')->nullable()->after('openai_api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'ai_chat_service', 
                'ai_embedding_service', 
                'ai_speech_to_text_service',
                'openai_api_key',
                'gemini_api_key'
            ]);
        });
    }
};
