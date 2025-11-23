<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->string('code', 50)->index();
            $table->string('name');
            $table->string('status', 20)->default('active')->index();
            $table->string('payment_terms', 20)->default('net_30');
            $table->decimal('qty_tolerance_percent', 5, 2)->default(5.00);
            $table->decimal('price_tolerance_percent', 5, 2)->default(2.00);
            $table->string('tax_id', 50)->nullable()->index();
            $table->json('bank_details')->nullable();
            $table->string('currency', 3)->default('MYR');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->json('address')->nullable();
            $table->timestamps();

            // // $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
