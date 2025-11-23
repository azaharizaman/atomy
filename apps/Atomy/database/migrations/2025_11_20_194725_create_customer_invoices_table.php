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
        Schema::create('customer_invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('customer_id')->index();
            $table->string('invoice_number', 100)->index();
            $table->date('invoice_date')->index();
            $table->date('due_date')->index();
            $table->string('currency', 3)->default('MYR');
            $table->decimal('exchange_rate', 12, 6)->default(1.0);
            $table->decimal('subtotal', 15, 2)->default(0.0);
            $table->decimal('tax_amount', 15, 2)->default(0.0);
            $table->decimal('total_amount', 15, 2)->default(0.0);
            $table->decimal('outstanding_balance', 15, 2)->default(0.0);
            $table->string('status', 20)->default('draft')->index();
            $table->ulid('gl_journal_id')->nullable()->index();
            $table->ulid('sales_order_id')->nullable()->index();
            $table->string('credit_term', 20)->default('net_30');
            $table->text('description')->nullable();
            $table->timestamps();

            // $table->unique(['tenant_id', 'customer_id', 'invoice_number'], 'unique_invoice_number');
            // $table->foreign('customer_id')->references('id')->on('parties')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_invoices');
    }
};
