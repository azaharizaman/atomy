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
            $table->integer('line_number');
            $table->ulid('account_id');
            $table->decimal('debit_amount', 19, 4)->default('0.0000');
            $table->decimal('credit_amount', 19, 4)->default('0.0000');
            $table->string('currency', 3)->default('MYR');
            $table->text('description')->nullable();
            $table->timestamps();

            // $table->unique(['journal_entry_id', 'line_number']);
            // $table->index('journal_entry_id');
            // $table->index('account_id');
            // $table->index(['account_id', 'journal_entry_id']);

            // $table->foreign('journal_entry_id')
                // ->references('id')
                // ->on('journal_entries')
                // ->onDelete('cascade');

            // $table->foreign('account_id')
                // ->references('id')
                // ->on('accounts')
                // ->onDelete('restrict');
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
