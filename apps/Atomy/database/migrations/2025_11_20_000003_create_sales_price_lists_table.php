<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_price_lists', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('name', 100);
            $table->string('currency_code', 3);
            $table->string('strategy', 30)->default('list_price');
            $table->dateTime('valid_from');
            $table->dateTime('valid_until')->nullable();
            $table->string('customer_id', 26)->nullable()->index()->comment('NULL = default for all customers');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'valid_from']);
            $table->index(['tenant_id', 'customer_id', 'is_active']);
        });

        Schema::create('price_list_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('price_list_id', 26)->index();
            $table->string('product_variant_id', 26)->index();
            $table->decimal('base_price', 19, 4);
            $table->json('discount_rule')->nullable();

            $table->foreign('price_list_id')
                ->references('id')
                ->on('sales_price_lists')
                ->onDelete('cascade');

            $table->unique(['price_list_id', 'product_variant_id']);
        });

        Schema::create('price_tiers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('price_list_item_id', 26)->index();
            $table->decimal('min_quantity', 19, 4);
            $table->decimal('max_quantity', 19, 4)->nullable()->comment('NULL = no upper limit');
            $table->decimal('unit_price', 19, 4);
            $table->decimal('discount_percentage', 5, 2)->nullable();

            $table->foreign('price_list_item_id')
                ->references('id')
                ->on('price_list_items')
                ->onDelete('cascade');

            $table->index(['price_list_item_id', 'min_quantity', 'max_quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_tiers');
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('sales_price_lists');
    }
};
