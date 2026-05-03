<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_bundle_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('evidence_bundle_id');
            $table->string('source_type');
            $table->ulid('source_id')->nullable();
            $table->string('artifact_kind');
            $table->string('label');
            $table->string('storage_path')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('included_at');
            $table->timestamps();

            $table->index(['tenant_id', 'evidence_bundle_id']);
            $table->index(['tenant_id', 'source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_bundle_items');
    }
};
