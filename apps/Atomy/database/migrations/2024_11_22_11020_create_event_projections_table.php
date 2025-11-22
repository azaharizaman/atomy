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
        Schema::create('event_projections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('projector_name')->unique();
            $table->string('last_processed_event_id', 26)->nullable();
            $table->integer('last_processed_version')->unsigned()->default(0);
            $table->dateTime('last_processed_at')->nullable();
            $table->enum('status', ['active', 'paused', 'error'])->default('active');
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            // Optimize for projection queries
            $table->index(['projector_name', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_projections');
    }
};
