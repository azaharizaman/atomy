<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nexus_idempotency_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->index();
            $table->string('operation_ref', 255);
            $table->string('client_key', 255);
            $table->text('request_fingerprint');
            $table->string('attempt_token', 36);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->json('result_envelope')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
            
            $table->unique(['tenant_id', 'operation_ref', 'client_key'], 'uk_tenant_operation_client');
            $table->index('expires_at', 'idx_expires_at');
            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nexus_idempotency_records');
    }
};
