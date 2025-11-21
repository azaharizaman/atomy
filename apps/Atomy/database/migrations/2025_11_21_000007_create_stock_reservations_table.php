<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('product_id')->index();
            $table->ulid('warehouse_id')->index();
            $table->decimal('quantity', 15, 4);
            $table->string('reference_type', 50); // sales_order, work_order, etc.
            $table->ulid('reference_id')->index();
            $table->timestamp('reserved_at');
            $table->timestamp('expires_at')->index();
            $table->string('status', 20)->default('active'); // active, fulfilled, released, expired
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamps();
            
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->index(['product_id', 'warehouse_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
