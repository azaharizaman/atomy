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
        Schema::create('statutory_rate_tables', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('country_code', 3)->index(); // ISO 3166-1 alpha-3 (MYS, SGP, etc.)
            $table->string('deduction_type', 50)->index(); // epf, socso, eis, pcb, etc.
            $table->date('effective_from')->index(); // When this rate becomes effective
            $table->date('effective_to')->nullable()->index(); // When this rate expires (null = current)
            $table->json('rate_config'); // Configuration for this rate (brackets, percentages, etc.)
            $table->timestamps();

            // Ensure no overlapping date ranges for same country/deduction
            // $table->index(['country_code', 'deduction_type', 'effective_from', 'effective_to']);

            // Index for current rate lookups
            // $table->index(['country_code', 'deduction_type', 'effective_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statutory_rate_tables');
    }
};
