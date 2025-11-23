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
        Schema::create('configuration_audit_checkpoints', function (Blueprint $table) {
            $table->string('id', 26)->primary(); // ULID
            $table->string('tenant_id', 26)->index();
            $table->string('scheme_id', 26)->index(); // Compliance scheme this checkpoint belongs to
            $table->string('checkpoint_name', 100);
            $table->string('target_package', 50); // Package being audited (e.g., 'Finance', 'Hrm')
            $table->string('checkpoint_type', 50); // 'feature_enabled', 'setting_configured', 'field_exists'
            $table->json('validation_rules'); // Rules for validation
            $table->string('status', 20)->default('pending'); // pending, passed, failed
            $table->text('failure_reason')->nullable(); // Why it failed
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            // Foreign key to compliance schemes
            // $table->foreign('scheme_id')->references('id')->on('compliance_schemes')->onDelete('cascade');

            // Index for efficient queries
            // $table->index(['tenant_id', 'scheme_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuration_audit_checkpoints');
    }
};
