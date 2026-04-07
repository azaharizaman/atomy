<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('code');
            $table->string('name');
            $table->string('email');
            $table->string('status')->default('pending');
            $table->string('domain')->nullable();
            $table->string('subdomain')->nullable();
            $table->string('database_name')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('locale')->default('en');
            $table->string('currency', 8)->default('USD');
            $table->string('date_format', 32)->default('Y-m-d');
            $table->string('time_format', 16)->default('H:i');
            $table->ulid('parent_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->unsignedBigInteger('storage_quota')->nullable();
            $table->unsignedBigInteger('storage_used')->default(0);
            $table->unsignedInteger('max_users')->nullable();
            $table->unsignedInteger('rate_limit')->nullable();
            $table->boolean('is_readonly')->default(false);
            $table->timestamp('billing_cycle_starts_at')->nullable();
            $table->unsignedTinyInteger('onboarding_progress')->default(0);
            $table->timestamp('retention_hold_until')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->unique('code');
            $table->unique('name');
            $table->unique('domain');
            $table->unique('subdomain');
            $table->index('email');
            $table->index('status');
            $table->index('parent_id');
            $table->index('database_name');
            $table->index('retention_hold_until');
            $table->foreign('parent_id')
                ->references('id')
                ->on('tenants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
