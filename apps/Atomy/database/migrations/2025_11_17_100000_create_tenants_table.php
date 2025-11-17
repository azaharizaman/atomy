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
        Schema::create('tenants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 50)->unique()->comment('Unique tenant code (e.g., ACME)');
            $table->string('name')->comment('Tenant display name');
            $table->string('email')->comment('Primary contact email');
            $table->string('domain')->nullable()->unique()->comment('Primary domain (e.g., acme.example.com)');
            $table->string('subdomain', 100)->nullable()->unique()->comment('Subdomain (e.g., acme)');
            $table->string('database_name')->nullable()->comment('Database name for multi-database strategy');
            
            // Status and lifecycle
            $table->enum('status', ['pending', 'active', 'suspended', 'archived', 'trial'])->default('pending');
            $table->timestamp('trial_ends_at')->nullable()->comment('Trial period end date');
            $table->timestamp('billing_cycle_start_date')->nullable()->comment('Billing cycle start');
            
            // Localization settings
            $table->string('timezone', 50)->default('UTC');
            $table->string('locale', 10)->default('en');
            $table->string('currency', 3)->default('USD');
            $table->string('date_format', 20)->default('Y-m-d');
            $table->string('time_format', 20)->default('H:i:s');
            
            // Enterprise features
            $table->ulid('parent_id')->nullable()->comment('Parent tenant ID for subsidiaries');
            $table->foreign('parent_id')->references('id')->on('tenants')->onDelete('set null');
            $table->bigInteger('storage_quota')->nullable()->comment('Storage limit in bytes (null = unlimited)');
            $table->bigInteger('storage_used')->default(0)->comment('Current storage usage in bytes');
            $table->integer('max_users')->nullable()->comment('Maximum users allowed (null = unlimited)');
            $table->integer('rate_limit')->nullable()->comment('API rate limit per minute (null = no limit)');
            $table->boolean('read_only')->default(false)->comment('Read-only mode for maintenance');
            $table->integer('onboarding_progress')->default(0)->comment('Setup completion percentage (0-100)');
            
            // Metadata
            $table->json('metadata')->nullable()->comment('Custom tenant settings and branding');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete timestamp for archival');
            
            // Indexes
            $table->index('status');
            $table->index('parent_id');
            $table->index('trial_ends_at');
            $table->index(['status', 'created_at']);
        });

        // Impersonation log table
        Schema::create('tenant_impersonations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->string('original_user_id')->comment('User who initiated impersonation');
            $table->text('reason')->nullable()->comment('Reason for impersonation');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_seconds')->nullable()->comment('Calculated duration');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            $table->index('tenant_id');
            $table->index('original_user_id');
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_impersonations');
        Schema::dropIfExists('tenants');
    }
};
