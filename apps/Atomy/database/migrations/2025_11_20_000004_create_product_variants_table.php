<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for product_variants table
 * 
 * Stores transactable product items with SKU, barcode, and physical attributes.
 * Can exist standalone or linked to a template.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            
            // Tenant scoping
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            
            // Template relationship (nullable for standalone products)
            $table->foreignUlid('template_id')->nullable()->constrained('product_templates')->cascadeOnDelete();
            
            // Unique identifiers
            $table->string('sku', 100)->comment('Stock Keeping Unit - unique within tenant');
            $table->string('barcode_value', 255)->nullable()->comment('Barcode value');
            $table->string('barcode_format', 50)->nullable()->comment('Barcode format (ean13, upca, code128, qr, custom)');
            
            // Product information
            $table->string('name');
            $table->text('description')->nullable();
            
            // Product classification
            $table->enum('type', ['storable', 'consumable', 'service'])->default('storable');
            $table->enum('tracking_method', ['none', 'lot_number', 'serial_number'])->default('none');
            
            // Unit of measure
            $table->string('base_uom', 50)->comment('Base unit of measure code');
            
            // Physical dimensions (stored as JSON using DimensionSet VO)
            $table->json('dimensions')->nullable()->comment('Physical dimensions (weight, length, width, height, volume)');
            
            // Classification
            $table->string('category_code', 100)->nullable();
            $table->foreign('category_code')->references('code')->on('categories')->nullOnDelete();
            
            // Default GL account codes (strings, resolved at application layer)
            $table->string('default_revenue_account_code', 50)->nullable();
            $table->string('default_cost_account_code', 50)->nullable();
            $table->string('default_inventory_account_code', 50)->nullable();
            
            // Status flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_saleable')->default(true);
            $table->boolean('is_purchaseable')->default(true);
            
            // Attribute values for template-based variants
            // e.g., {"COLOR": "Red", "SIZE": "M"}
            $table->json('attribute_values')->nullable()->comment('Attribute combination for template variants');
            
            // Additional metadata
            $table->json('metadata')->nullable()->comment('Additional variant attributes');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['tenant_id', 'sku'], 'variants_tenant_sku_unique');
            $table->unique(['tenant_id', 'barcode_value'], 'variants_tenant_barcode_unique');
            $table->index(['tenant_id', 'template_id'], 'variants_template_index');
            $table->index(['tenant_id', 'category_code'], 'variants_category_index');
            $table->index(['tenant_id', 'type'], 'variants_type_index');
            $table->index(['tenant_id', 'is_active'], 'variants_tenant_active_index');
            $table->index(['tenant_id', 'is_saleable'], 'variants_saleable_index');
            $table->index(['tenant_id', 'is_purchaseable'], 'variants_purchaseable_index');
            $table->index('sku');
            $table->index('barcode_value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
