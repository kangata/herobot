<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bot_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->onDelete('cascade');
            $table->morphs('connectable');
            $table->timestamps();

            $table->unique(['bot_id', 'connectable_id', 'connectable_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bot_connections');
    }
};