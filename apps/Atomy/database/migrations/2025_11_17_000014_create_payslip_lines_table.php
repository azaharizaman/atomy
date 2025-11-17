<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('payslip_id')->constrained('payslips')->cascadeOnDelete();
            $table->foreignUlid('component_id')->nullable()->constrained('payroll_components')->nullOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type'); // earning, deduction, contribution
            $table->decimal('amount', 15, 2);
            $table->boolean('is_statutory')->default(false);
            $table->integer('display_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'payslip_id']);
            $table->index(['payslip_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_lines');
    }
};
