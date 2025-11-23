<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for categories table
 * 
 * Implements hierarchical product categories with adjacency list pattern.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            
            // Tenant scoping
            // $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            
            // Category identification
            $table->string('code', 100)->comment('Unique code within tenant');
            $table->string('name');
            $table->text('description')->nullable();
            
            // Hierarchical structure (adjacency list)
            // $table->foreignUlid('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            
            // Display ordering
            $table->integer('sort_order')->default(0);
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            // $table->unique(['tenant_id', 'code'], 'categories_tenant_code_unique');
            // $table->index(['tenant_id', 'parent_id'], 'categories_hierarchy_index');
            // $table->index(['tenant_id', 'is_active'], 'categories_tenant_active_index');
            // $table->index('parent_id');
            // $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
