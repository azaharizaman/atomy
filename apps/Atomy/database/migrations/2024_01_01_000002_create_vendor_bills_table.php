<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_bills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->uuid('vendor_id')->index();
            $table->string('bill_number', 100)->index();
            $table->date('bill_date')->index();
            $table->date('due_date')->index();
            $table->string('currency', 3)->default('MYR');
            $table->decimal('exchange_rate', 12, 6)->default(1.0);
            $table->decimal('subtotal', 15, 2)->default(0.0);
            $table->decimal('tax_amount', 15, 2)->default(0.0);
            $table->decimal('total_amount', 15, 2)->default(0.0);
            $table->string('status', 20)->default('draft')->index();
            $table->string('matching_status', 20)->default('pending')->index();
            $table->uuid('gl_journal_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->timestamps();

            // // $table->unique(['tenant_id', 'vendor_id', 'bill_number']);
            // // $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_bills');
    }
};
