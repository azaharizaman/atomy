<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // $table->foreignUlid('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->string('line_reference')->unique();
            $table->integer('line_number');
            // $table->foreignUlid('requisition_line_id')->nullable()->constrained('requisition_lines')->onDelete('set null');
            $table->string('item_code')->index();
            $table->text('description');
            $table->decimal('quantity', 19, 4);
            $table->string('unit');
            $table->decimal('unit_price', 19, 4);
            $table->decimal('line_total', 19, 4);
            $table->decimal('quantity_received', 19, 4)->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // $table->index(['purchase_order_id', 'line_number']);
            // $table->index('line_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_lines');
    }
};
