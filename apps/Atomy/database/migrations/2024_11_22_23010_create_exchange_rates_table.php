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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->char('id', 26)->primary(); // ULID
            $table->string('from_currency', 3); // Source currency
            $table->string('to_currency', 3); // Target currency
            $table->decimal('rate', 20, 6); // Exchange rate (6 decimal precision)
            $table->date('effective_date'); // Rate effective date
            $table->string('source', 50)->nullable(); // Rate source (e.g., 'ECB', 'Fixer.io')
            $table->timestamps();

            // Indexes for common queries
            $table->index(['from_currency', 'to_currency']);
            $table->index('effective_date');
            $table->unique(['from_currency', 'to_currency', 'effective_date']);

            // Foreign keys
            $table->foreign('from_currency')->references('code')->on('currencies')->onDelete('restrict');
            $table->foreign('to_currency')->references('code')->on('currencies')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
