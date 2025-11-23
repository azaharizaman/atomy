<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // // $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            // // $table->foreignUlid('employee_id')->constrained('employees')->cascadeOnDelete();
            // // $table->foreignUlid('review_template_id')->nullable()->constrained('workflow_definitions')->nullOnDelete();
            $table->date('review_period_start');
            $table->date('review_period_end');
            $table->string('review_type');
            // // $table->foreignUlid('reviewer_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('reviewer_comments')->nullable();
            $table->text('employee_comments')->nullable();
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('goals_for_next_period')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // // $table->index(['tenant_id', 'employee_id', 'status']);
            // // $table->index(['tenant_id', 'reviewer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
