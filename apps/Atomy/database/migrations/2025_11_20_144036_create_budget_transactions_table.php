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
        Schema::create('budget_transactions', function (Blueprint $table) {
            // Primary key (ULID)
            $table->string('id', 26)->primary();
            
            // Transaction classification
            $table->string('budget_id', 26)->index();
            $table->string('transaction_type', 20); // commitment, actual, reversal
            $table->string('source_document_id', 26)->index(); // PO, JE, etc.
            $table->string('source_document_type', 50)->nullable(); // purchase_order, journal_entry
            
            // Transaction amounts
            $table->decimal('amount', 19, 4);
            $table->string('currency', 3);
            
            // Line-item granularity for detailed tracking
            $table->text('line_item_description')->nullable();
            $table->string('account_id', 26)->nullable(); // GL account for actual transactions
            $table->string('cost_center_id', 26)->nullable();
            
            // Transaction state
            $table->boolean('is_released')->default(false); // For commitments
            $table->timestamp('released_at')->nullable();
            $table->string('released_by', 26)->nullable();
            
            // Reversal support
            $table->string('reversed_by_transaction_id', 26)->nullable();
            $table->boolean('is_reversal')->default(false);
            
            // Audit
            $table->string('created_by', 26)->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['budget_id', 'transaction_type']);
            $table->index(['source_document_id', 'transaction_type']);
            $table->index('created_at');
            
            // Foreign keys
            // $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_transactions');
    }
};
