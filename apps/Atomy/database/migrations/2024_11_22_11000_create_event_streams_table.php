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
        Schema::create('event_streams', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('event_id', 26)->unique()->index(); // ULID
            $table->string('aggregate_id', 26)->index();
            $table->string('event_type')->index();
            $table->integer('version')->unsigned();
            $table->dateTime('occurred_at')->index();
            $table->json('payload');
            $table->string('causation_id', 26)->nullable();
            $table->string('correlation_id', 26)->nullable()->index();
            $table->string('tenant_id', 26)->index();
            $table->string('user_id', 26)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Ensure version uniqueness per aggregate
            $table->unique(['aggregate_id', 'version']);
            
            // Optimize for stream queries
            $table->index(['aggregate_id', 'version']);
            $table->index(['event_type', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_streams');
    }
};
