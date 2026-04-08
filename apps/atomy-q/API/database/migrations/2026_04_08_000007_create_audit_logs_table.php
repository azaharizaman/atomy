<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->ulid('id')->primary();

            // Required by Nexus\AuditLogger\Contracts\AuditLogInterface.
            $table->string('log_name')->index();
            $table->text('description');

            $table->string('event')->nullable()->index();
            $table->integer('level')->default(1)->index();
            $table->string('batch_uuid')->nullable()->index();

            $table->string('subject_type')->nullable()->index();
            $table->string('subject_id')->nullable()->index();
            $table->string('causer_type')->nullable()->index();
            $table->string('causer_id')->nullable()->index();

            $table->ulid('tenant_id')->nullable()->index();

            $table->string('ip_address')->nullable()->index();
            $table->text('user_agent')->nullable();

            $table->json('properties');
            $table->integer('retention_days')->default(90);
            $table->timestamp('expires_at')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
