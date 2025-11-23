<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // $table->foreignUlid('goods_receipt_note_id')->constrained('goods_receipt_notes')->onDelete('cascade');
            $table->integer('line_number');
            $table->string('po_line_reference')->index();
            $table->decimal('quantity_received', 19, 4);
            $table->string('unit');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // $table->foreign('po_line_reference')->references('line_reference')->on('purchase_order_lines')->onDelete('cascade');
            // $table->index(['goods_receipt_note_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_lines');
    }
};
