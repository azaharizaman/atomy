<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('bill_id')->index();
            $table->uuid('vendor_id')->index();
            $table->decimal('scheduled_amount', 15, 2)->default(0.0);
            $table->date('due_date')->index();
            $table->decimal('early_payment_discount_percent', 5, 2)->default(0.0);
            $table->date('early_payment_discount_date')->nullable();
            $table->string('status', 20)->default('scheduled')->index();
            $table->uuid('payment_id')->nullable()->index();
            $table->uuid('gl_journal_id')->nullable()->index();
            $table->string('currency', 3)->default('MYR');
            $table->timestamps();

            // // $table->foreign('bill_id')->references('id')->on('vendor_bills')->onDelete('restrict');
            // // $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_schedules');
    }
};
