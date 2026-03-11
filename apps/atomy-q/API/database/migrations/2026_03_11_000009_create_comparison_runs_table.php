<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparison_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('rfq_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('idempotency_key', 128)->nullable();
            $table->boolean('is_preview')->default(false);
            $table->ulid('created_by')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('matrix_payload')->nullable();
            $table->json('scoring_payload')->nullable();
            $table->json('approval_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('readiness_payload')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('discarded_at')->nullable();
            $table->string('discarded_by', 128)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'rfq_id']);
            $table->index(['tenant_id', 'status']);
            $table->unique(['tenant_id', 'rfq_id', 'idempotency_key'], 'comparison_runs_tenant_rfq_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparison_runs');
    }
};
