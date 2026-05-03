<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_bundles', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('rfq_id');
            $table->ulid('comparison_run_id')->nullable();
            $table->ulid('approval_id')->nullable();
            $table->ulid('award_id')->nullable();
            $table->string('type')->default('award_justification');
            $table->string('status')->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->json('manifest')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'rfq_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'rfq_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_bundles');
    }
};
