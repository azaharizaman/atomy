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
        Schema::create('statutory_report_instances', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('report_id', 26)->index(); // Reference to statutory_reports
            $table->integer('version')->default(1); // Version number
            $table->timestamp('generated_at'); // When this version was generated
            $table->string('generated_by', 26)->nullable(); // User who generated it
            $table->text('file_path'); // Path to this version's file
            $table->string('checksum', 64)->nullable(); // SHA-256 checksum for integrity
            $table->timestamp('created_at');

            // Foreign key to statutory_reports
            // $table->foreign('report_id')->references('id')->on('statutory_reports')->onDelete('cascade');

            // Unique version per report
            // $table->unique(['report_id', 'version']);

            // Index for queries
            // $table->index(['report_id', 'generated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statutory_report_instances');
    }
};
