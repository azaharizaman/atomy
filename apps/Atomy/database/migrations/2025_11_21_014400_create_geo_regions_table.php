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
        Schema::create('geo_regions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('code', 50); // e.g., 'US-CA', 'MY-14'
            $table->string('name', 255); // e.g., 'California', 'Kuala Lumpur'
            $table->json('boundary_polygon'); // Array of {lat, lng} points
            $table->timestamps();

            // Composite unique index for tenant + code
            $table->unique(['tenant_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geo_regions');
    }
};
