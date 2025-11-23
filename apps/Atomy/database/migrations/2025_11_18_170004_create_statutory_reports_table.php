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
        Schema::create('statutory_reports', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('report_type', 100)->index(); // profit_loss, ssm_br, lhdn_pcb, etc.
            $table->date('start_date')->index(); // Report period start
            $table->date('end_date')->index(); // Report period end
            $table->string('format', 20); // json, xml, xbrl, csv, pdf, excel
            $table->string('status', 20)->default('draft')->index(); // draft, generated, filed, rejected
            $table->text('file_path')->nullable(); // Path to generated file
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();

            // Index for efficient queries
            // $table->index(['tenant_id', 'report_type', 'status']);
            // $table->index(['tenant_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statutory_reports');
    }
};
