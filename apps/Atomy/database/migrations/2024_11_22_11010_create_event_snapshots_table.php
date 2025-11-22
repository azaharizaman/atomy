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
        Schema::create('event_snapshots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('aggregate_id', 26)->index();
            $table->integer('version')->unsigned();
            $table->json('state');
            $table->string('checksum', 64); // SHA-256 hash
            $table->timestamps();
            
            // Ensure one snapshot per aggregate per version
            $table->unique(['aggregate_id', 'version']);
            
            // Optimize for snapshot retrieval
            $table->index(['aggregate_id', 'version', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_snapshots');
    }
};
