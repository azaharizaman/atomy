<?php

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
        Schema::create('route_optimization_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->enum('optimization_type', ['tsp', 'vrp']);
            $table->unsignedInteger('stop_count');
            $table->unsignedInteger('execution_time_ms');
            $table->json('constraint_violations')->nullable(); // Array of ConstraintViolation
            $table->json('metadata')->nullable(); // Algorithm details, improvement %
            $table->timestamp('created_at');

            // Index for performance analysis
            // $table->index(['tenant_id', 'optimization_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_optimization_logs');
    }
};
