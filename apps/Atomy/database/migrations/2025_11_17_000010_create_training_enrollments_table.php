<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_enrollments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->timestamp('enrolled_at');
            $table->string('status')->default('enrolled');
            $table->timestamp('completed_at')->nullable();
            $table->decimal('attendance_percentage', 5, 2)->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->decimal('passing_score', 5, 2)->nullable();
            $table->boolean('is_passed')->nullable();
            $table->boolean('certificate_issued')->default(false);
            $table->timestamp('certificate_issued_at')->nullable();
            $table->text('feedback')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['training_id', 'employee_id']);
            $table->index(['tenant_id', 'employee_id', 'status']);
            $table->index(['tenant_id', 'training_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_enrollments');
    }
};
