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
        Schema::table('balances', function (Blueprint $table) {
            $table->bigInteger('amount')->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->bigInteger('amount')->change();
        });

        Schema::table('token_usages', function (Blueprint $table) {
            $table->bigInteger('credits')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('balances', function (Blueprint $table) {
            $table->decimal('amount', 10, 6)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount', 10, 6)->change();
        });

        Schema::table('token_usages', function (Blueprint $table) {
            $table->decimal('credits', 12, 6)->change();
        });
    }
};
