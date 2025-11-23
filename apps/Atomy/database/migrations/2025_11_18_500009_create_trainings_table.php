<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // // $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('provider')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('duration_hours', 6, 2)->nullable();
            $table->string('location')->nullable();
            $table->integer('max_participants')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('status')->default('planned');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // // $table->index(['tenant_id', 'status']);
            // // $table->index(['tenant_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
