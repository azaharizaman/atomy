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
        Schema::create('receivable_schedules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('invoice_id')->index();
            $table->ulid('customer_id')->index();
            $table->decimal('scheduled_amount', 15, 2)->default(0.0);
            $table->date('due_date')->index();
            $table->decimal('early_payment_discount_percent', 5, 2)->default(0.0);
            $table->date('early_payment_discount_date')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->ulid('receipt_id')->nullable()->index();
            $table->string('currency', 3)->default('MYR');
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('customer_invoices')->onDelete('restrict');
            $table->foreign('customer_id')->references('id')->on('parties')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receivable_schedules');
    }
};
