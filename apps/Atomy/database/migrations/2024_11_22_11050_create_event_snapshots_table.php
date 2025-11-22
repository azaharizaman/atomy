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
     * Create event_snapshots table for dynamic snapshot thresholds.
     * 
     * Design: Snapshot threshold adapts based on event volume:
     * - Low volume (<100 events/month): Every 100 events
     * - Medium volume (100-1000): Every 50 events
     * - High volume (>1000): Every 25 events
     */
    public function up(): void
    {
        Schema::create('event_snapshots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('aggregate_id', 255)->index();
            $table->string('aggregate_type', 255);
            $table->integer('last_event_version');
            $table->jsonb('snapshot_data');
            $table->timestamp('created_at', 6)->useCurrent();
            
            // Unique constraint: one snapshot per aggregate version
            $table->unique(['aggregate_id', 'aggregate_type', 'last_event_version'], 'idx_snapshots_aggregate_version');
            
            // Index for latest snapshot queries
            $table->index(['aggregate_id', 'aggregate_type', 'last_event_version'], 'idx_snapshots_latest');
            
            // GIN index for snapshot data queries
            $table->rawIndex('(snapshot_data)', 'idx_snapshots_data_gin', 'gin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_snapshots');
    }
};
