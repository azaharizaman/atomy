<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Event Sourcing: Event Streams Table
     * 
     * Requirements satisfied:
     * - ARC-EVS-7005: All database migrations in application layer
     * - BUS-EVS-7103: Events are immutable - once appended, cannot be modified or deleted
     * - BUS-EVS-7104: Each event MUST contain aggregate ID, event type, version, timestamp, payload
     * - FUN-EVS-7212: Generate unique EventId (ULID) for each event
     * - FUN-EVS-7213: Track event metadata: causation ID, correlation ID
     * - FUN-EVS-7214: Support event enrichment with tenant context, user context
     */
    public function up(): void
    {
        Schema::create('event_streams', function (Blueprint $table) {
            // Primary identifier
            $table->string('event_id', 26)->primary(); // ULID (26 chars)
            
            // Stream identification
            $table->string('aggregate_id', 100)->index();
            $table->string('aggregate_type', 100); // e.g., 'account', 'inventory_item'
            $table->unsignedInteger('version'); // For optimistic concurrency control
            
            // Event metadata
            $table->string('event_type', 255); // Fully qualified class name
            $table->json('payload'); // Serialized event data
            $table->json('metadata')->nullable(); // Additional context
            
            // Causality tracking
            $table->string('causation_id', 26)->nullable(); // Event that triggered this event
            $table->string('correlation_id', 100)->nullable(); // Distributed trace ID
            
            // Multi-tenancy and user tracking
            $table->string('tenant_id', 100)->index();
            $table->string('user_id', 100)->nullable();
            
            // Timestamp
            $table->dateTime('occurred_at'); // When the event occurred
            $table->timestamps(); // Laravel created_at (immutable after creation)
            
            // Indexes for performance
            // $table->unique(['aggregate_id', 'version']); // Optimistic concurrency
            // $table->index(['event_type', 'occurred_at']); // Query by event type
            // $table->index(['tenant_id', 'aggregate_id']); // Tenant isolation
            // $table->index('occurred_at'); // Temporal queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_streams');
    }
};
