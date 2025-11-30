<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inv_stock_levels', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('product_id');
            $table->ulid('warehouse_id');
            $table->decimal('quantity', 20, 4)->default(0);
            $table->decimal('reserved_quantity', 20, 4)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id']);
            $table->index('product_id');
            $table->index('warehouse_id');
        });

        Schema::create('inv_lots', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('product_id');
            $table->string('lot_number', 100);
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('initial_quantity', 20, 4);
            $table->decimal('remaining_quantity', 20, 4);
            $table->decimal('unit_cost', 20, 4);
            $table->ulid('supplier_id')->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->json('attributes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'lot_number']);
            $table->index('product_id');
            $table->index('expiry_date');
            $table->index('supplier_id');
        });

        Schema::create('inv_serials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('product_id');
            $table->string('serial_number', 100);
            $table->ulid('warehouse_id');
            $table->ulid('lot_id')->nullable();
            $table->string('status', 20)->default('available');
            $table->decimal('cost', 20, 4)->default(0);
            $table->ulid('current_owner_id')->nullable();
            $table->string('current_owner_type', 50)->nullable();
            $table->json('attributes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'serial_number']);
            $table->index('product_id');
            $table->index('warehouse_id');
            $table->index('lot_id');
            $table->index('status');

            $table->foreign('lot_id')
                ->references('id')
                ->on('inv_lots')
                ->onDelete('set null');
        });

        Schema::create('inv_stock_movements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('product_id');
            $table->ulid('warehouse_id');
            $table->string('movement_type', 30);
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_cost', 20, 4)->default(0);
            $table->decimal('total_cost', 20, 4)->default(0);
            $table->ulid('reference_id')->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->ulid('lot_id')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id']);
            $table->index('movement_type');
            $table->index('reference_id');
            $table->index(['product_id', 'created_at']);
            $table->index('lot_id');

            $table->foreign('lot_id')
                ->references('id')
                ->on('inv_lots')
                ->onDelete('set null');
        });

        Schema::create('inv_reservations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('product_id');
            $table->ulid('warehouse_id');
            $table->decimal('quantity', 20, 4);
            $table->ulid('reference_id');
            $table->string('reference_type', 50);
            $table->string('status', 20)->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'warehouse_id', 'status']);
            $table->index(['reference_id', 'reference_type']);
            $table->index('status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inv_reservations');
        Schema::dropIfExists('inv_stock_movements');
        Schema::dropIfExists('inv_serials');
        Schema::dropIfExists('inv_lots');
        Schema::dropIfExists('inv_stock_levels');
    }
};
