<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('product_id')->index();
            $table->ulid('warehouse_id')->index();
            $table->decimal('quantity_on_hand', 15, 4)->default(0);
            $table->decimal('quantity_reserved', 15, 4)->default(0);
            $table->decimal('quantity_available', 15, 4)->storedAs('quantity_on_hand - quantity_reserved');
            $table->string('valuation_method', 20)->default('weighted_average');
            $table->decimal('average_cost', 15, 4)->nullable();
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();
            
            // $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            // $table->unique(['tenant_id', 'product_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
