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
        Schema::create('budgets', function (Blueprint $table) {
            // Primary key (ULID)
            $table->string('id', 26)->primary();
            
            // Core budget attributes
            $table->string('name');
            $table->string('period_id', 26)->index();
            $table->string('budget_type', 20); // operational, capital, project, revenue
            $table->string('status', 30)->default('draft'); // draft, approved, active, closed, locked, under_investigation, simulated
            
            // Dual-currency support (functional + presentation)
            $table->decimal('allocated_amount_functional', 19, 4);
            $table->string('functional_currency', 3);
            $table->decimal('allocated_amount_presentation', 19, 4)->nullable();
            $table->string('presentation_currency', 3)->nullable();
            $table->decimal('exchange_rate_snapshot', 12, 6)->nullable();
            
            // Computed amounts (denormalized for performance)
            $table->decimal('committed_amount', 19, 4)->default(0);
            $table->decimal('actual_amount', 19, 4)->default(0);
            $table->decimal('available_amount', 19, 4)->storedAs('allocated_amount_functional - committed_amount - actual_amount');
            
            // Organizational dimensions
            $table->string('department_id', 26)->nullable()->index();
            $table->string('project_id', 26)->nullable()->index();
            $table->string('account_id', 26)->nullable()->index(); // GL account linkage
            
            // Hierarchical budgets support
            $table->string('parent_budget_id', 26)->nullable()->index();
            $table->unsignedTinyInteger('hierarchy_level')->default(0);
            
            // Rollover configuration
            $table->string('rollover_policy', 20)->default('expire'); // expire, auto_roll_unused, require_approval
            
            // Methodology
            $table->string('budgeting_methodology', 20)->default('incremental'); // incremental, zero_based, activity_based
            
            // Simulation support
            $table->string('base_budget_id', 26)->nullable()->index(); // For simulation budgets
            $table->boolean('is_simulation')->default(false);
            
            // Audit fields
            $table->string('created_by', 26)->nullable();
            $table->string('updated_by', 26)->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['period_id', 'status']);
            $table->index(['department_id', 'period_id']);
            $table->index(['budget_type', 'status']);
            
            // Foreign keys (would be defined when those tables exist)
            // $table->foreign('period_id')->references('id')->on('periods')->onDelete('restrict');
            // $table->foreign('department_id')->references('id')->on('departments')->onDelete('restrict');
            // $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
            // $table->foreign('parent_budget_id')->references('id')->on('budgets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
