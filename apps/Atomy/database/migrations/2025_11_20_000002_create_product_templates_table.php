<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for product_templates table
 * 
 * Stores conceptual product definitions with shared attributes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            
            // Tenant scoping
            // $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            
            // Template identification
            $table->string('code', 100)->comment('Unique code within tenant');
            $table->string('name');
            $table->text('description')->nullable();
            
            // Classification
            $table->string('category_code', 100)->nullable();
            // $table->foreign('category_code')->references('code')->on('categories')->nullOnDelete();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Additional data
            $table->json('metadata')->nullable()->comment('Additional template attributes');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            // $table->unique(['tenant_id', 'code'], 'templates_tenant_code_unique');
            // $table->index(['tenant_id', 'is_active'], 'templates_tenant_active_index');
            // $table->index(['tenant_id', 'category_code'], 'templates_category_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_templates');
    }
};
