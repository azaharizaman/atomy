<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Event Sourcing: Projections Table
     * 
     * Requirements satisfied:
     * - ARC-EVS-7005: All database migrations in application layer
     * - FUN-EVS-7218: Resume projection from last processed event on failure/restart
     * - REL-EVS-7410: Projection lag monitoring with alerting
     */
    public function up(): void
    {
        Schema::create('event_projections', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Projector identification
            $table->string('projector_name', 100)->unique();
            
            // State tracking
            $table->string('last_processed_event_id', 26)->nullable(); // ULID of last processed event
            $table->unsignedBigInteger('processed_count')->default(0); // Total events processed
            $table->dateTime('last_processed_at')->nullable(); // When last event was processed
            
            // Status tracking
            $table->enum('status', ['running', 'stopped', 'failed'])->default('stopped');
            $table->text('error_message')->nullable(); // Last error if failed
            $table->dateTime('last_error_at')->nullable();
            
            // Multi-tenancy
            $table->string('tenant_id', 100)->nullable()->index();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            // $table->index('status');
            // $table->index('last_processed_at'); // For lag monitoring
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
