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
        Schema::create('gl_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('type', 50); // ASSET, LIABILITY, EQUITY, REVENUE, EXPENSE
            $table->char('currency', 3)->default('MYR');
            $table->ulid('parent_id')->nullable();
            $table->boolean('is_header')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')
                ->on('gl_accounts')
                ->onDelete('restrict');

            $table->index(['type', 'is_active']);
            $table->index('parent_id');
        });

        Schema::create('gl_journal_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('entry_number', 50)->unique();
            $table->date('date');
            $table->string('reference', 100)->nullable();
            $table->text('description');
            $table->string('status', 20)->default('draft'); // draft, posted, reversed
            $table->ulid('created_by');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['date', 'status']);
            $table->index('status');
            $table->index('created_by');
        });

        Schema::create('gl_journal_entry_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('journal_entry_id');
            $table->ulid('account_id');
            // Use DECIMAL(20, 4) for monetary amounts to provide sufficient precision for most currencies.
            // 4 decimal places is standard for amounts; higher precision (6-8 decimals) would be used for rates/exchange if needed.
            $table->decimal('debit_amount', 20, 4)->default(0);
            $table->decimal('credit_amount', 20, 4)->default(0);
            $table->char('currency', 3)->default('MYR');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('journal_entry_id')
                ->references('id')
                ->on('gl_journal_entries')
                ->onDelete('cascade');

            $table->foreign('account_id')
                ->references('id')
                ->on('gl_accounts')
                ->onDelete('restrict');

            $table->index('account_id');
            $table->index('journal_entry_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gl_journal_entry_lines');
        Schema::dropIfExists('gl_journal_entries');
        Schema::dropIfExists('gl_accounts');
    }
};
