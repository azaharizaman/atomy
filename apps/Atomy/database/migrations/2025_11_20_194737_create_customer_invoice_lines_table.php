<?php

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
        Schema::create('customer_invoice_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('invoice_id')->index();
            $table->integer('line_number');
            $table->string('description', 255);
            $table->decimal('quantity', 15, 4)->default(0.0);
            $table->decimal('unit_price', 15, 4)->default(0.0);
            $table->decimal('line_amount', 15, 2)->default(0.0);
            $table->string('gl_account', 20);
            $table->string('tax_code', 20)->nullable();
            $table->ulid('product_id')->nullable();
            $table->string('sales_order_line_reference', 100)->nullable()->index();
            $table->timestamps();

            // $table->unique(['invoice_id', 'line_number'], 'unique_invoice_line');
            // $table->foreign('invoice_id')->references('id')->on('customer_invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_invoice_lines');
    }
};
