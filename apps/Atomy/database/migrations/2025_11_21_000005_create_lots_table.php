<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('lot_number', 100)->index();
            $table->ulid('product_id')->index();
            $table->date('expiry_date');
            $table->date('manufacturing_date')->nullable();
            $table->string('supplier_lot_number', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // $table->unique(['tenant_id', 'lot_number']);
            // $table->index(['product_id', 'expiry_date']); // For FEFO queries
        });
        
        Schema::create('lot_stock_levels', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('lot_id');
            $table->ulid('warehouse_id');
            $table->decimal('quantity', 15, 4)->default(0);
            $table->timestamps();
            
            // $table->foreign('lot_id')->references('id')->on('lots')->onDelete('cascade');
            // $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            // $table->unique(['lot_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lot_stock_levels');
        Schema::dropIfExists('lots');
    }
};
