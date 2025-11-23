<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('transfer_number', 50)->unique();
            $table->ulid('product_id')->index();
            $table->ulid('from_warehouse_id');
            $table->ulid('to_warehouse_id');
            $table->decimal('quantity', 15, 4);
            $table->decimal('received_quantity', 15, 4)->nullable();
            $table->string('status', 20)->default('pending'); // pending, in_transit, completed, cancelled
            $table->ulid('reference_id')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->ulid('created_by')->nullable();
            $table->timestamps();
            
            // $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            // $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            // $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
