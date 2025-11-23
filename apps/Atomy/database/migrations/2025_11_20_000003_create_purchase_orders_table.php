<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('number')->unique();
            $table->string('vendor_id')->index();
            $table->string('creator_id')->index();
            // $table->foreignUlid('requisition_id')->nullable()->constrained('requisitions')->onDelete('set null');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'partially_received', 'fully_received', 'closed', 'cancelled'])->default('draft')->index();
            $table->enum('po_type', ['standard', 'blanket', 'release'])->default('standard')->index();
            $table->string('blanket_po_id')->nullable()->index();
            $table->decimal('total_amount', 19, 4)->default(0);
            $table->decimal('total_committed_value', 19, 4)->nullable();
            $table->decimal('total_released_value', 19, 4)->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->string('approver_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // $table->index(['tenant_id', 'status']);
            // $table->index(['tenant_id', 'vendor_id']);
            // $table->index(['tenant_id', 'po_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
