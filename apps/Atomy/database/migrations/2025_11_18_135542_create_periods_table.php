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
        Schema::create('periods', function (Blueprint $table) {
            $table->ulid('id')->primary();
            
            // Period classification
            $table->string('type', 50)->index()->comment('Period type: accounting, inventory, payroll, manufacturing');
            $table->string('status', 50)->index()->comment('Period status: pending, open, closed, locked');
            
            // Date range
            $table->date('start_date')->index()->comment('Period start date (inclusive)');
            $table->date('end_date')->index()->comment('Period end date (inclusive)');
            
            // Metadata
            $table->string('fiscal_year', 10)->index()->comment('Fiscal year (e.g., 2024)');
            $table->string('name', 100)->comment('Period name (e.g., JAN-2024, 2024-Q1)');
            $table->text('description')->nullable()->comment('Optional description');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for performance
            // // $table->index(['type', 'status'], 'idx_type_status');
            // // $table->index(['type', 'start_date', 'end_date'], 'idx_type_date_range');
            // // $table->index(['type', 'fiscal_year'], 'idx_type_fiscal_year');
            
            // Unique constraint: No overlapping periods of the same type
            // // $table->unique(['type', 'start_date', 'end_date'], 'uq_type_date_range');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
