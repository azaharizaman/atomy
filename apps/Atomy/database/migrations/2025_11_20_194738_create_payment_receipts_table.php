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
        Schema::create('payment_receipts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('customer_id')->index();
            $table->string('receipt_number', 100)->unique();
            $table->date('receipt_date')->index();
            $table->decimal('amount', 15, 2)->default(0.0);
            $table->string('currency', 3)->default('MYR');
            $table->decimal('exchange_rate', 12, 6)->default(1.0);
            $table->decimal('amount_in_invoice_currency', 15, 2)->nullable();
            $table->string('payment_method', 20);
            $table->string('bank_account', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->ulid('gl_journal_id')->nullable()->index();
            $table->json('allocations')->nullable();
            $table->timestamps();

            // $table->foreign('customer_id')->references('id')->on('parties')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
    }
};
