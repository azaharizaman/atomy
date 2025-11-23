<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Event Sourcing: Snapshots Table
     * 
     * Requirements satisfied:
     * - ARC-EVS-7005: All database migrations in application layer
     * - BUS-EVS-7106: Snapshots created periodically to optimize replay performance
     * - FUN-EVS-7209: Create snapshots automatically after N events
     * - REL-EVS-7406: Snapshots validated before use (checksum verification)
     */
    public function up(): void
    {
        Schema::create('event_snapshots', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Aggregate identification
            $table->string('aggregate_id', 100)->index();
            $table->string('aggregate_type', 100);
            $table->unsignedInteger('version'); // Version at which snapshot was taken
            
            // Snapshot data
            $table->json('state'); // Serialized aggregate state
            $table->string('checksum', 64); // SHA-256 hash for validation
            
            // Multi-tenancy
            $table->string('tenant_id', 100)->index();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            // $table->unique(['aggregate_id', 'version']); // One snapshot per version
            // $table->index(['tenant_id', 'aggregate_id']); // Tenant isolation
            // $table->index('created_at'); // For cleanup/archival
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
