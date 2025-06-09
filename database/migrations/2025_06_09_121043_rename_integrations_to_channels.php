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
        Schema::rename('integrations', 'channels');

        Schema::table('chat_histories', function (Blueprint $table) {
            $table->renameColumn('integration_id', 'channel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('channels', 'integrations');

        Schema::table('chat_histories', function (Blueprint $table) {
            $table->renameColumn('channel_id', 'integration_id');
        });
    }
};
