<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nexus_quotations', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('sourcing_event_id')->index();
            $table->index(['tenant_id', 'sourcing_event_id'], 'nexus_tenant_event_idx');
            $table->ulid('vendor_id')->index();
            $table->string('status');
            $table->json('normalization_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nexus_quotations');
    }
};
