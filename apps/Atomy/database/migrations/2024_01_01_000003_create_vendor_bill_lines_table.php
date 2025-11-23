<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_bill_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bill_id')->index();
            $table->integer('line_number');
            $table->string('description');
            $table->decimal('quantity', 15, 4)->default(0.0);
            $table->decimal('unit_price', 15, 4)->default(0.0);
            $table->decimal('line_amount', 15, 2)->default(0.0);
            $table->string('gl_account', 20);
            $table->string('tax_code', 20)->nullable();
            $table->string('po_line_reference', 100)->nullable()->index();
            $table->string('grn_line_reference', 100)->nullable()->index();

            // // $table->unique(['bill_id', 'line_number']);
            // // $table->foreign('bill_id')->references('id')->on('vendor_bills')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_bill_lines');
    }
};
