<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id')->index();
            $table->string('number')->unique();
            // $table->foreignUlid('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->string('receiver_id')->index();
            $table->date('received_date');
            $table->enum('status', ['draft', 'confirmed', 'payment_authorized'])->default('draft')->index();
            $table->string('warehouse_location')->nullable();
            $table->text('notes')->nullable();
            $table->string('payment_authorizer_id')->nullable();
            $table->timestamp('payment_authorized_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // $table->index(['tenant_id', 'status']);
            // $table->index(['tenant_id', 'purchase_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_notes');
    }
};
