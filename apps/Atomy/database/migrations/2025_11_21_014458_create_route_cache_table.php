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
        Schema::create('route_cache', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('cache_key', 255); // Hash of stops + constraints
            $table->binary('compressed_route'); // Gzipped OptimizedRoute JSON
            $table->unsignedInteger('size_bytes'); // Uncompressed size
            $table->unsignedInteger('compressed_size_bytes'); // Compressed size
            $table->dateTime('created_at');
            $table->dateTime('expires_at')->index();

            // Composite unique index for tenant + cache_key
            // $table->unique(['tenant_id', 'cache_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_cache');
    }
};
