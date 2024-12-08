<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('type'); // 'topup', 'usage', etc.
            $table->string('description');
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->string('payment_id')->nullable(); // Xendit payment ID
            $table->string('payment_method')->nullable(); // Payment method used
            $table->string('external_id')->unique()->nullable(); // Our reference ID
            $table->json('payment_details')->nullable(); // Store additional payment details
            $table->timestamp('expired_at')->nullable(); // Payment expiration date
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
