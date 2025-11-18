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
        Schema::create('period_closes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('period_id')->unique()->index();
            $table->string('close_type', 20); // month, year
            $table->string('status', 20)->index(); // open, in_progress, closed, reopened
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_by')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->string('reopened_by')->nullable();
            $table->string('reason')->nullable(); // Reason for reopening
            $table->json('validation_results')->nullable();
            $table->json('closing_entries')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['close_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_closes');
    }
};
