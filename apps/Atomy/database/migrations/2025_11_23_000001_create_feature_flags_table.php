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
        Schema::create('feature_flags', function (Blueprint $table) {
            // Primary key (ULID)
            $table->ulid('id')->primary();

            // Tenant scoping (null = global flag)
            $table->ulid('tenant_id')->nullable();

            // Flag identity
            $table->string('name', 100); // Max 100 chars per validation

            // Flag state
            $table->boolean('enabled')->default(false);
            $table->string('strategy', 50); // Enum: system_wide, percentage_rollout, etc.
            $table->json('value')->nullable(); // Strategy-specific value (percentage, user list, etc.)
            $table->string('override', 20)->nullable(); // Enum: force_on, force_off

            // Metadata
            $table->json('metadata')->nullable(); // Custom data (created_by, description, tags, etc.)
            $table->string('checksum', 64); // SHA-256 hash for cache validation

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->unique(['tenant_id', 'name'], 'feature_flags_tenant_name_unique');
            $table->index('name', 'feature_flags_name_index'); // For cross-tenant lookups
            $table->index('enabled', 'feature_flags_enabled_index'); // For filtering enabled flags
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
