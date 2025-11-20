<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('payment_number', 50)->unique();
            $table->date('payment_date')->index();
            $table->decimal('amount', 15, 2)->default(0.0);
            $table->string('currency', 3)->default('MYR');
            $table->decimal('exchange_rate', 12, 6)->default(1.0);
            $table->string('payment_method', 20);
            $table->string('bank_account', 50);
            $table->string('reference', 100)->nullable();
            $table->string('status', 20)->default('scheduled')->index();
            $table->uuid('gl_journal_id')->nullable()->index();
            $table->json('allocations')->nullable(); // Array of {bill_id, amount}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
