<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // // // $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('employee_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->date('date_of_birth');
            $table->date('hire_date');
            $table->date('confirmation_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('status')->default('probationary');
            $table->ulid('manager_id')->nullable()->index();
            // // // $table->foreignUlid('department_id')->nullable()->constrained('departments')->nullOnDelete();
            // // // $table->foreignUlid('office_id')->nullable()->constrained('offices')->nullOnDelete();
            $table->string('job_title')->nullable();
            $table->string('employment_type')->default('full_time');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // // // $table->index(['tenant_id', 'status']);
            // // // $table->index(['tenant_id', 'employee_code']);
            // // // $table->index('department_id');
        });
        
        // Add self-referencing foreign key after table creation
        Schema::table('employees', function (Blueprint $table) {
            // // // $table->foreign('manager_id')->references('id')->on('employees')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
