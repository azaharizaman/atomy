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
        Schema::create('financial_statements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('statement_type', 50)->index(); // balance_sheet, income_statement, cash_flow, etc.
            $table->string('entity_id')->index();
            $table->string('period_id')->index();
            $table->json('data'); // Full statement data
            $table->integer('version')->default(1);
            $table->string('compliance_standard', 50)->nullable(); // GAAP, IFRS, etc.
            $table->timestamp('generated_at');
            $table->string('generated_by');
            $table->boolean('locked')->default(false)->index();
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['entity_id', 'period_id', 'statement_type']);
            $table->index(['entity_id', 'statement_type', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_statements');
    }
};
