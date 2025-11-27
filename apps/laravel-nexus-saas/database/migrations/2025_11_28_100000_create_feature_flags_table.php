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
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('enabled')->default(false);
            $table->string('strategy', 32)->default('system_wide');
            $table->json('value')->nullable();
            $table->string('override', 16)->nullable();
            $table->json('metadata')->default('[]');
            $table->timestamps();
            $table->string('created_by', 26)->nullable();
            $table->string('updated_by', 26)->nullable();

            // Unique constraint for tenant + flag name
            $table->unique(['tenant_id', 'name'], 'unique_tenant_flag');

            // Indexes for common queries
            $table->index('enabled', 'idx_feature_flags_enabled');
            $table->index('strategy', 'idx_feature_flags_strategy');
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
