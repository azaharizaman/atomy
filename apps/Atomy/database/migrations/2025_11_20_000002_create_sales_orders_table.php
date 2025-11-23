<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('order_number', 50)->index();
            $table->string('customer_id', 26)->index();
            $table->dateTime('order_date');
            $table->string('status', 30)->default('draft')->index();
            $table->string('currency_code', 3);
            $table->decimal('exchange_rate', 19, 8)->nullable()->comment('Locked at confirmation for foreign currency');
            $table->decimal('subtotal', 19, 4)->default(0);
            $table->decimal('tax_amount', 19, 4)->default(0);
            $table->decimal('discount_amount', 19, 4)->default(0);
            $table->decimal('total', 19, 4)->default(0);
            $table->json('discount_rule')->nullable();
            $table->string('payment_term', 30);
            $table->dateTime('payment_due_date')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('customer_purchase_order', 100)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->string('confirmed_by', 26)->nullable();
            
            // Future-proof fields for Phase 2
            $table->boolean('is_recurring')->default(false)->comment('Phase 2: Recurring subscriptions');
            $table->json('recurrence_rule')->nullable()->comment('Phase 2: Subscription frequency/interval');
            $table->string('salesperson_id', 26)->nullable()->index()->comment('Phase 2: Sales commission tracking');
            $table->decimal('commission_percentage', 5, 2)->nullable()->comment('Phase 2: Sales commission rate');
            $table->string('preferred_warehouse_id', 26)->nullable()->index()->comment('Phase 2: Multi-warehouse fulfillment');
            
            $table->timestamps();

            // $table->unique(['tenant_id', 'order_number']);
            // $table->index(['tenant_id', 'status', 'order_date']);
            // $table->index(['tenant_id', 'customer_id', 'status']);
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('sales_order_id', 26)->index();
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

            // $table->foreign('sales_order_id')
                // ->references('id')
                // ->on('sales_orders')
                // ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_lines');
        Schema::dropIfExists('sales_orders');
    }
};
