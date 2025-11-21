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
        Schema::create('geo_cache', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('address', 500);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('provider', 50); // 'google-maps' or 'nominatim'
            $table->json('metadata')->nullable();
            $table->dateTime('cached_at');
            $table->dateTime('expires_at')->index();

            // Composite unique index for tenant + address
            $table->unique(['tenant_id', 'address']);
            
            // Index for coordinate-based queries
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geo_cache');
    }
};
