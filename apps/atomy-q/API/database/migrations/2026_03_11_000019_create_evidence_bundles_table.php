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
            $table->ulid('approval_id');
            $table->string('type')->default('quote_evidence');
            $table->string('storage_path');
            $table->string('checksum', 64)->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();

            $table->index('approval_id');
            $table->index(['tenant_id', 'approval_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidence_bundles');
    }
};
