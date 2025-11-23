<?php

declare(strict_types=1);

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
        Schema::create('scheduled_jobs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('job_type', 100)->index();
            $table->ulid('target_id')->index();
            $table->dateTime('run_at')->index();
            $table->string('status', 50)->index();
            $table->json('payload')->nullable();
            $table->json('recurrence')->nullable();
            $table->integer('max_retries')->default(3);
            $table->integer('retry_count')->default(0);
            $table->integer('priority')->default(0)->index();
            $table->integer('occurrence_count')->default(0);
            $table->json('last_result')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Composite indexes for common queries
            // $table->index(['status', 'run_at']);
            // $table->index(['job_type', 'status']);
            // $table->index(['target_id', 'job_type']);
            // $table->index(['status', 'priority', 'run_at']);
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_jobs');
    }
};
