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
        Schema::create('early_accesses', function (Blueprint $table) {
            $table->id();
            $table->string('organization_name');
            $table->string('email');
            $table->string('website')->nullable();
            $table->enum('organization_type', ['school', 'social', 'business', 'other']);
            $table->text('description');
            $table->boolean('is_approved')->default(false);
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('early_accesses');
    }
};
