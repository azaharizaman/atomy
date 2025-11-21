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
        Schema::create('unapplied_cash', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('customer_id')->index();
            $table->ulid('receipt_id');
            $table->decimal('amount', 15, 2)->default(0.0);
            $table->string('currency', 3)->default('MYR');
            $table->date('received_date');
            $table->ulid('gl_journal_id')->nullable();
            $table->string('status', 20)->default('unapplied')->index();
            $table->ulid('applied_to_invoice_id')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('parties')->onDelete('restrict');
            $table->foreign('receipt_id')->references('id')->on('payment_receipts')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unapplied_cash');
    }
};
