<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_layers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('product_id')->index();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('remaining_quantity', 15, 4);
            $table->date('received_date');
            $table->ulid('receipt_reference_id')->nullable();
            $table->timestamps();
            
            $table->index(['product_id', 'received_date']); // For FIFO consumption
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_layers');
    }
};
