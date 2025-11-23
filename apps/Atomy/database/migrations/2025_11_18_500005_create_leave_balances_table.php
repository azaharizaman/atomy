<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // // $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            // // $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            // // $table->foreignUlid('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->integer('year');
            $table->decimal('entitled_days', 5, 2)->default(0);
            $table->decimal('used_days', 5, 2)->default(0);
            $table->decimal('carried_forward_days', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // // $table->unique(['employee_id', 'leave_type_id', 'year']);
            // // $table->index(['tenant_id', 'employee_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
