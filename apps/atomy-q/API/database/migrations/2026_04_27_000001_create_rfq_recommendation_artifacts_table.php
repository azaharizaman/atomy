<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfq_recommendation_artifacts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id');
            $table->ulid('rfq_id');
            $table->string('feature_key', 64);
            $table->string('status', 32);
            $table->json('canonical_payload');
            $table->json('provenance')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'rfq_id']);
            $table->unique(['tenant_id', 'rfq_id', 'feature_key'], 'rfq_recommendation_artifacts_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_recommendation_artifacts');
    }
};
