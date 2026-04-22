<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisition_selected_vendors', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('rfq_id');
            $table->ulid('vendor_id');
            $table->ulid('selected_by_user_id')->nullable();
            $table->timestamp('selected_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'rfq_id', 'vendor_id'], 'requisition_selected_vendors_tenant_rfq_vendor_unique');
            $table->index(['tenant_id', 'rfq_id']);
            $table->index(['tenant_id', 'vendor_id']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('rfq_id')->references('id')->on('rfqs')->cascadeOnDelete();
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();
            $table->foreign('selected_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_selected_vendors');
    }
};
