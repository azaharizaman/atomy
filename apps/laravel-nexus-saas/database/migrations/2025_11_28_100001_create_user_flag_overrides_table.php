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
        Schema::create('user_flag_overrides', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('user_id', 26)->index();
            $table->string('flag_name', 100)->index();
            $table->boolean('enabled')->default(false);
            $table->json('value')->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->string('created_by', 26)->nullable();
            $table->string('updated_by', 26)->nullable();

            // Unique constraint for tenant + user + flag name
            $table->unique(['tenant_id', 'user_id', 'flag_name'], 'unique_user_flag_override');

            // Index for common queries
            $table->index('enabled', 'idx_user_flag_overrides_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_flag_overrides');
    }
};
