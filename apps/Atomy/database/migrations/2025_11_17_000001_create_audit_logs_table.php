<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for audit_logs table
 * Satisfies: ARC-AUD-0005 (migrations in application layer)
 * Implements all requirements from BUS-AUD and FUN-AUD specs
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // Required fields per BUS-AUD-0145
            $table->string('log_name')->index();
            $table->text('description');
            
            // Subject (entity being acted upon)
            $table->string('subject_type')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->index(['subject_type', 'subject_id'], 'subject_index');
            
            // Causer (who performed the action)
            // NULL for system activities per BUS-AUD-0148
            $table->string('causer_type')->nullable()->index();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->index(['causer_type', 'causer_id'], 'causer_index');
            
            // Additional data (before/after state, metadata)
            // Satisfies FUN-AUD-0186
            $table->json('properties')->nullable();
            
            // Event type (created, updated, deleted, accessed)
            // Satisfies FUN-AUD-0185
            $table->string('event')->nullable()->index();
            
            // Audit level (1=Low, 2=Medium, 3=High, 4=Critical)
            // Satisfies BUS-AUD-0146
            $table->tinyInteger('level')->default(2)->index();
            
            // Batch UUID for grouping related operations
            // Satisfies BUS-AUD-0150, FUN-AUD-0193
            $table->uuid('batch_uuid')->nullable()->index();
            
            // User context per FUN-AUD-0187
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Multi-tenancy support per FUN-AUD-0188
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            
            // Retention policy per BUS-AUD-0147, FUN-AUD-0194
            $table->integer('retention_days')->default(90);
            $table->timestamp('expires_at')->index();
            
            // Required timestamp per BUS-AUD-0145
            $table->timestamp('created_at');
            
            // Indexes for performance
            $table->index('created_at');
            $table->index(['log_name', 'created_at']);
            $table->index(['level', 'created_at']);
            $table->index(['expires_at', 'created_at']);
            
            // Full-text search support per FUN-AUD-0189
            // Note: MySQL/MariaDB specific, adjust for other databases
            if (config('database.default') === 'mysql') {
                DB::statement('ALTER TABLE audit_logs ADD FULLTEXT fulltext_index (log_name, description)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
