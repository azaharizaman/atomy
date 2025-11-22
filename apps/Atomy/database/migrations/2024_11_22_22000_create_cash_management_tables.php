<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('tenant_id', 26);
            $table->string('account_code', 50)->unique();
            $table->char('gl_account_id', 26);
            $table->string('account_number', 50);
            $table->string('bank_name', 100);
            $table->string('bank_code', 20);
            $table->enum('account_type', ['checking', 'savings', 'credit_card', 'money_market', 'line_of_credit']);
            $table->enum('status', ['active', 'inactive', 'closed', 'suspended'])->default('active');
            $table->char('currency', 3);
            $table->decimal('current_balance', 20, 4)->default(0);
            $table->timestamp('last_reconciled_at')->nullable();
            $table->json('csv_import_config')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('gl_account_id');
            $table->foreign('gl_account_id')->references('id')->on('accounts')->onDelete('restrict');
        });

        Schema::create('bank_statements', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('tenant_id', 26);
            $table->char('bank_account_id', 26);
            $table->string('statement_number', 50);
            $table->date('period_start');
            $table->date('period_end');
            $table->string('statement_hash', 64)->unique();
            $table->decimal('opening_balance', 20, 4);
            $table->decimal('closing_balance', 20, 4);
            $table->decimal('total_debit', 20, 4)->default(0);
            $table->decimal('total_credit', 20, 4)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->timestamp('imported_at');
            $table->char('imported_by', 26);
            $table->timestamp('reconciled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['bank_account_id', 'period_start', 'period_end']);
            $table->index('tenant_id');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('bank_statement_id', 26);
            $table->date('transaction_date');
            $table->text('description');
            $table->enum('transaction_type', ['deposit', 'withdrawal', 'transfer', 'fee', 'interest', 'check', 'atm', 'direct_debit', 'direct_credit', 'reversal', 'other']);
            $table->decimal('amount', 20, 4);
            $table->decimal('balance', 20, 4)->nullable();
            $table->string('reference', 100)->nullable();
            $table->char('reconciliation_id', 26)->nullable();
            $table->timestamps();

            $table->index(['bank_statement_id', 'transaction_date']);
            $table->index('reconciliation_id');
            $table->foreign('bank_statement_id')->references('id')->on('bank_statements')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_statements');
        Schema::dropIfExists('bank_accounts');
    }
};
