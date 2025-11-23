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
        Schema::create('budget_revisions', function (Blueprint $table) {
            // Primary key (ULID)
            $table->string('id', 26)->primary();
            
            // Revision tracking
            $table->string('budget_id', 26)->index();
            $table->unsignedInteger('revision_number');
            
            // Snapshot of previous state
            $table->decimal('previous_allocated_amount', 19, 4);
            $table->decimal('new_allocated_amount', 19, 4);
            $table->string('currency', 3);
            
            // Previous status
            $table->string('previous_status', 30);
            $table->string('new_status', 30);
            
            // Change metadata
            $table->string('change_type', 30); // amendment, status_change, transfer_in, transfer_out
            $table->text('reason');
            $table->text('justification')->nullable();
            
            // Related entities
            $table->string('related_budget_id', 26)->nullable(); // For transfers
            $table->string('workflow_approval_id', 26)->nullable();
            
            // Audit
            $table->string('created_by', 26)->nullable();
            $table->timestamp('created_at');
            
            // Indexes
            // $table->index(['budget_id', 'revision_number']);
            // $table->index('created_at');
            
            // Foreign keys
            // // $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_revisions');
    }
};
