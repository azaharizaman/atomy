<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create event_projections table for read model tracking.
     * 
     * Purpose: Track projection rebuild state and lag monitoring.
     * Used by UpdateAccountBalanceProjection listener to maintain current balances.
     */
    public function up(): void
    {
        Schema::create('event_projections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('projection_name', 255)->unique();
            $table->string('aggregate_type', 255);
            $table->bigInteger('last_processed_event_id')->default(0);
            $table->integer('last_processed_version')->default(0);
            $table->timestamp('last_processed_at', 6)->nullable();
            $table->enum('status', ['active', 'rebuilding', 'failed', 'paused'])->default('active');
            $table->jsonb('metadata')->nullable();
            $table->timestamps(6);
            
            // Index for projection queries by aggregate type
            $table->index(['aggregate_type', 'status'], 'idx_projections_aggregate_status');
            
            // Index for lag monitoring queries
            $table->index(['status', 'last_processed_at'], 'idx_projections_status_processed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_projections');
    }
};
