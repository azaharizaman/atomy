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
        Schema::create('consolidation_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('parent_statement_id')->index();
            $table->string('rule_type', 50); // elimination_intercompany, elimination_investment, etc.
            $table->string('source_entity_id')->index();
            $table->string('target_entity_id')->index();
            $table->decimal('amount', 20, 4);
            $table->string('account_code', 50)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('parent_statement_id')
                  ->references('id')
                  ->on('financial_statements')
                  ->onDelete('cascade');

            // Composite indexes for intercompany queries
            $table->index(['source_entity_id', 'target_entity_id']);
            $table->index(['rule_type', 'source_entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consolidation_entries');
    }
};
