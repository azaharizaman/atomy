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
        Schema::create('reports_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('query_id')->index(); // References analytics_query_definitions
            $table->uuid('owner_id')->index(); // User or entity owning the report
            $table->string('format', 20); // pdf, excel, csv, json, html
            $table->string('schedule_type', 20)->nullable(); // once, daily, weekly, monthly, cron
            $table->json('schedule_config')->nullable(); // Cron expression, recurrence details
            $table->json('recipients')->nullable(); // Array of NotifiableInterface IDs
            $table->json('parameters')->nullable(); // Default query parameters
            $table->json('template_config')->nullable(); // Custom logos, CSS, headers, footers
            $table->boolean('is_active')->default(true);
            $table->uuid('tenant_id')->nullable()->index();
            $table->timestamps();

            // Indexes for performance
            // $table->index('owner_id');
            // $table->index('query_id');
            // $table->index(['is_active', 'schedule_type']); // For findDueForGeneration()
            // $table->index(['tenant_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports_definitions');
    }
};
