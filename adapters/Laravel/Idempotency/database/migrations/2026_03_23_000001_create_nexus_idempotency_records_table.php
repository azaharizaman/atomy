<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nexus_idempotency_records', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id', 128);
            $table->string('operation_ref', 128);
            $table->string('client_key', 256);
            $table->string('request_fingerprint', 512);
            $table->string('attempt_token', 128);
            $table->string('status', 32);
            $table->longText('result_envelope')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('last_transition_at')->useCurrent();
            $table->unique(['tenant_id', 'operation_ref', 'client_key'], 'nexus_idempotency_tenant_op_client');
            $table->index('status', 'nexus_idempotency_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nexus_idempotency_records');
    }
};
