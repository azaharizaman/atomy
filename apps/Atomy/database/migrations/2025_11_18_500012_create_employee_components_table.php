<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_components', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // // $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            // // $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            // // $table->foreignUlid('component_id')->constrained('payroll_components')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('percentage_value', 5, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // // $table->index(['tenant_id', 'employee_id', 'is_active']);
            // // $table->index(['tenant_id', 'component_id']);
            // // $table->index(['effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_components');
    }
};
