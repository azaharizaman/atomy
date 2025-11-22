<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Create partitioned event_streams table for Finance GL and Inventory event sourcing.
     * 
     * Design Decisions:
     * - PostgreSQL PARTITION BY RANGE (occurred_at) for fiscal year isolation
     * - Initial partitions: 2024, 2025 (30-day pre-creation via scheduled command)
     * - GIN indexes on JSONB columns for event payload queries
     * - BRIN index on occurred_at for efficient partition pruning
     * - 7-year retention policy (archival to S3/Azure Blob via scheduled command)
     */
    public function up(): void
    {
        // Create parent partitioned table
        DB::statement("
            CREATE TABLE event_streams (
                id BIGSERIAL,
                aggregate_id VARCHAR(255) NOT NULL,
                aggregate_type VARCHAR(255) NOT NULL,
                event_type VARCHAR(255) NOT NULL,
                event_version INT NOT NULL DEFAULT 1,
                payload JSONB NOT NULL,
                metadata JSONB DEFAULT '{}'::jsonb,
                occurred_at TIMESTAMP(6) NOT NULL,
                created_at TIMESTAMP(6) NOT NULL DEFAULT NOW(),
                PRIMARY KEY (id, occurred_at)
            ) PARTITION BY RANGE (occurred_at)
        ");

        // Create indexes on parent table (inherited by partitions)
        DB::statement("CREATE INDEX idx_event_streams_aggregate ON event_streams (aggregate_id, aggregate_type, occurred_at DESC)");
        DB::statement("CREATE INDEX idx_event_streams_type ON event_streams (event_type, occurred_at DESC)");
        DB::statement("CREATE INDEX idx_event_streams_payload_gin ON event_streams USING GIN (payload jsonb_path_ops)");
        DB::statement("CREATE INDEX idx_event_streams_metadata_gin ON event_streams USING GIN (metadata jsonb_path_ops)");
        DB::statement("CREATE INDEX idx_event_streams_occurred_brin ON event_streams USING BRIN (occurred_at) WITH (pages_per_range = 128)");

        // Create partition for 2024 (fiscal year 2024: 2024-01-01 to 2024-12-31)
        DB::statement("
            CREATE TABLE event_streams_2024 PARTITION OF event_streams
            FOR VALUES FROM ('2024-01-01 00:00:00') TO ('2025-01-01 00:00:00')
        ");

        // Create partition for 2025 (fiscal year 2025: 2025-01-01 to 2025-12-31)
        DB::statement("
            CREATE TABLE event_streams_2025 PARTITION OF event_streams
            FOR VALUES FROM ('2025-01-01 00:00:00') TO ('2026-01-01 00:00:00')
        ");

        // Create partition for 2026 (30-day pre-creation: created on 2025-12-02)
        DB::statement("
            CREATE TABLE event_streams_2026 PARTITION OF event_streams
            FOR VALUES FROM ('2026-01-01 00:00:00') TO ('2027-01-01 00:00:00')
        ");

        // Create unique constraint on aggregate stream to prevent duplicate events
        // Note: This is enforced per partition, ensuring event ordering within aggregate
        DB::statement("
            CREATE UNIQUE INDEX idx_event_streams_aggregate_version 
            ON event_streams (aggregate_id, aggregate_type, event_version, occurred_at)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS event_streams CASCADE");
    }
};
