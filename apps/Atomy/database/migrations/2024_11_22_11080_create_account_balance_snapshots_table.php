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
     * Creates dynamic snapshot storage for event replay optimization.
     * Snapshots are created when event count exceeds configurable threshold.
     */
    public function up(): void
    {
        Schema::create('account_balance_snapshots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('account_id')->constrained('accounts')->cascadeOnDelete();
            
            // Snapshot metadata
            $table->unsignedBigInteger('event_version'); // Event version at snapshot
            $table->timestamp('snapshot_at'); // When snapshot was created
            
            // Snapshot data (balances at this point in time)
            $table->jsonb('snapshot_data'); // {debit_balance, credit_balance, current_balance}
            
            // Dynamic threshold configuration
            $table->unsignedInteger('threshold')->default(100); // Events before next snapshot
            $table->unsignedInteger('events_since_snapshot')->default(0); // Counter
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['tenant_id', 'account_id', 'event_version']);
            $table->index(['account_id', 'event_version']); // For replay queries
            $table->index('snapshot_data')->using('gin'); // For JSONB queries
        });
        
        DB::statement("COMMENT ON TABLE account_balance_snapshots IS 'Dynamic snapshots for event replay optimization. Threshold auto-adjusts based on account activity.'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_balance_snapshots');
    }
};
