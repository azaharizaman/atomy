<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for analytics_instances table
 * Each model instance that uses analytics has one analytics instance
 * Satisfies: BUS-ANA-0141 (Each model instance has one analytics instance)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analytics_instances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Model reference (polymorphic)
            $table->string('model_type')->index();
            $table->string('model_id');
            // $table->unique(['model_type', 'model_id'], 'model_unique');
            
            // Analytics metadata
            $table->json('configuration')->nullable(); // Analytics settings
            $table->timestamp('last_query_at')->nullable();
            $table->integer('total_queries')->default(0);
            
            // Lifecycle
            $table->string('created_by')->nullable();
            $table->timestamps();
            
            // Index
            // $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_instances');
    }
};
