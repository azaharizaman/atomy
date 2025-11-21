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
        Schema::create('reports_generated', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_definition_id')->nullable()->index(); // Nullable for ad-hoc reports
            $table->string('format', 20); // pdf, excel, csv, json, html
            $table->string('file_path', 500)->nullable(); // storage:// URI
            $table->bigInteger('file_size_bytes')->default(0);
            $table->string('retention_tier', 20)->default('active'); // active, archived, purged
            $table->timestamp('generated_at'); // Indexed via composite indexes below
            $table->integer('duration_ms');
            $table->boolean('is_successful')->default(true);
            $table->text('error')->nullable();
            $table->uuid('query_result_id')->nullable(); // References analytics_query_results
            $table->uuid('generated_by')->index(); // User ID
            $table->uuid('tenant_id')->nullable()->index();
            $table->timestamps();

            // Indexes for performance
            $table->index(['report_definition_id', 'generated_at']);
            $table->index(['retention_tier', 'generated_at']); // For cleanup queries
            $table->index(['tenant_id', 'generated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports_generated');
    }
};
