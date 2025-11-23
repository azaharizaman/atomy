<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinary_cases', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // // $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            // // $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('case_number')->unique();
            $table->date('incident_date');
            $table->date('reported_date');
            // // $table->foreignUlid('reported_by')->constrained('employees')->cascadeOnDelete();
            $table->string('category');
            $table->string('severity');
            $table->text('description');
            $table->string('status')->default('reported');
            $table->text('investigation_notes')->nullable();
            // // $table->foreignUlid('investigated_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('investigation_completed_at')->nullable();
            $table->text('resolution')->nullable();
            $table->text('action_taken')->nullable();
            $table->timestamp('closed_at')->nullable();
            // // $table->foreignUlid('closed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('follow_up_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // // $table->index(['tenant_id', 'employee_id', 'status']);
            // // $table->index(['tenant_id', 'case_number']);
            // // $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_cases');
    }
};
