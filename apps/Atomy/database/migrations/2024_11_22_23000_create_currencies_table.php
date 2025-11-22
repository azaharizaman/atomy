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
        Schema::create('currencies', function (Blueprint $table) {
            $table->char('id', 26)->primary(); // ULID
            $table->string('code', 3)->unique(); // ISO 4217 code
            $table->string('name', 100); // Currency name
            $table->string('symbol', 10); // Currency symbol
            $table->unsignedTinyInteger('decimal_places')->default(2); // Decimal precision
            $table->char('numeric_code', 3); // ISO 4217 numeric code
            $table->boolean('is_active')->default(true); // Active status
            $table->timestamps();

            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
