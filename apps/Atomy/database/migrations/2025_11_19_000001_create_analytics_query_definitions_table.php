<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for analytics_query_definitions table
 * Stores analytics query definitions (can be model-based or global)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_query_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Query metadata
            $table->string('name')->index();
            $table->string('type')->index(); // aggregation, prediction, report, etc.
            $table->text('description')->nullable();
            
            // Model association (optional - queries can be global)
            $table->string('model_type')->nullable()->index();
            $table->string('model_id')->nullable();
            // $table->index(['model_type', 'model_id'], 'query_definitions_model_index');
            
            // Query configuration (JSON)
            $table->json('parameters')->nullable();
            $table->json('guards')->nullable(); // Guard conditions
            $table->json('data_sources')->nullable(); // Data source configs
            
            // Execution settings
            $table->boolean('requires_transaction')->default(true);
            $table->integer('timeout')->default(300); // seconds
            $table->boolean('supports_parallel_execution')->default(false);
            
            // Metadata
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            // $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_query_definitions');
    }
};
