<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('knowledge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained();
            $table->string('name');
            $table->enum('type', ['qa', 'text', 'file']);
            $table->json('qa')->nullable();
            $table->text('text')->nullable();
            $table->string('filepath')->nullable();
            $table->string('filename')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->enum('status', ['pending', 'indexing', 'completed', 'failed'])->default('pending');
            $table->timestamps();
        });
        
        Schema::create('knowledge_vectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_id')->constrained();
            $table->text('text');
            $table->json('vector');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('knowledge_vectors');
        Schema::dropIfExists('knowledge');
    }
};
