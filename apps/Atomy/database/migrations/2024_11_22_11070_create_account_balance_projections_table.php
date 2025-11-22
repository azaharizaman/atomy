<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates read model for account balances with:
     * - Optimistic locking (updated_at versioning)
     * - Event versioning (last_event_version tracking)
     * - Hot account tracking (access_count for ZINCRBY)
     * - Last accessed timestamp for LRU caching
     */
    public function up(): void
    {
        Schema::create('account_balance_projections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('account_id')->constrained('accounts')->cascadeOnDelete();
            
            // Balance tracking
            $table->decimal('debit_balance', 19, 4)->default(0);
            $table->decimal('credit_balance', 19, 4)->default(0);
            $table->decimal('current_balance', 19, 4)->default(0); // Calculated: debit - credit
            
            // Event sourcing metadata
            $table->unsignedBigInteger('last_event_version')->default(0); // Track last processed event
            $table->timestamp('last_event_at')->nullable(); // When last event was processed
            
            // Hot account tracking (LRU cache)
            $table->unsignedBigInteger('access_count')->default(0); // For ZINCRBY in Redis
            $table->timestamp('last_accessed_at')->nullable(); // For TTL management
            
            // Optimistic locking
            $table->timestamps(); // updated_at used for version check
            
            // Indexes
            $table->unique(['tenant_id', 'account_id']); // One projection per account
            $table->index('last_event_version'); // For projection rebuild queries
            $table->index('access_count'); // For hot account detection
        });
        
        // Add comment for documentation
        DB::statement("COMMENT ON TABLE account_balance_projections IS 'Read model for account balances. Rebuilt from event_streams via UpdateAccountBalanceProjection listener. Supports optimistic locking and hot account tracking.'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_balance_projections');
    }
};
