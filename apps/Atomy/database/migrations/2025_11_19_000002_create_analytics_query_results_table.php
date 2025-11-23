<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for analytics_query_results table
 * Stores execution history and results
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_query_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Query reference
            $table->uuid('query_id')->index();
            $table->string('query_name')->index();
            
            // Model association
            $table->string('model_type');
            $table->string('model_id');
            // $table->index(['model_type', 'model_id'], 'query_results_model_index');
            
            // Execution metadata
            $table->string('executed_by')->nullable()->index();
            $table->timestamp('executed_at')->index();
            $table->integer('duration_ms')->default(0);
            
            // Result data
            $table->boolean('is_successful')->default(true)->index();
            $table->text('error')->nullable();
            $table->json('result_data')->nullable();
            $table->json('metadata')->nullable(); // Timing, resources used, etc.
            
            // Context
            $table->string('tenant_id')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance queries
            // $table->index('created_at');
            // $table->index(['query_id', 'executed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_query_results');
    }
};
