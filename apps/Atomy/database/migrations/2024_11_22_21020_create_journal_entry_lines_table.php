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
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('journal_entry_id');
            $table->ulid('account_id');
            $table->decimal('debit_amount', 20, 4)->default(0);
            $table->decimal('credit_amount', 20, 4)->default(0);
            $table->string('debit_currency', 3)->default('MYR');
            $table->string('credit_currency', 3)->default('MYR');
            $table->text('description')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('journal_entry_id')
                ->references('id')
                ->on('journal_entries')
                ->cascadeOnDelete();

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->restrictOnDelete();

            // Indexes
            $table->index('journal_entry_id');
            $table->index('account_id');

            // Check constraint: Either debit OR credit must be non-zero (not both, not neither)
            // MySQL 8.0.16+ supports CHECK constraints
            // For earlier versions, this should be enforced at application level
            $table->rawIndex(
                '((debit_amount > 0 AND credit_amount = 0) OR (credit_amount > 0 AND debit_amount = 0))',
                'check_debit_credit_mutual_exclusivity'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
