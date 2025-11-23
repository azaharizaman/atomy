<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_quotations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('quote_number', 50)->index();
            $table->string('customer_id', 26)->index();
            $table->dateTime('quote_date');
            $table->dateTime('valid_until')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->string('currency_code', 3);
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('total', 19, 4)->default(0);
            $table->json('discount_rule')->nullable();
            $table->text('notes')->nullable();
            $table->string('prepared_by', 26);
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->string('converted_to_order_id', 26)->nullable()->index();
            $table->timestamps();

            // $table->unique(['tenant_id', 'quote_number']);
            // $table->index(['tenant_id', 'status', 'quote_date']);
        });

        Schema::create('sales_quotation_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('quotation_id', 26)->index();
            $table->string('product_variant_id', 26)->index();
            $table->decimal('quantity', 19, 4);
            $table->string('uom_code', 10);
            $table->decimal('unit_price', 19, 4);
            $table->decimal('line_subtotal', 19, 4);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('line_total', 19, 4);
            $table->json('discount_rule')->nullable();
            $table->text('line_notes')->nullable();
            $table->integer('line_sequence')->default(0);

            // $table->foreign('quotation_id')
                // ->references('id')
                // ->on('sales_quotations')
                // ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_quotation_lines');
        Schema::dropIfExists('sales_quotations');
    }
};
