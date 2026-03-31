<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debriefs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('rfq_id');
            $table->ulid('award_id');
            $table->ulid('vendor_id');
            $table->text('message')->nullable();
            $table->timestamp('debriefed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'rfq_id']);
            $table->index(['tenant_id', 'award_id']);
            $table->unique(['tenant_id', 'award_id', 'vendor_id'], 'debriefs_tenant_award_vendor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debriefs');
    }
};
