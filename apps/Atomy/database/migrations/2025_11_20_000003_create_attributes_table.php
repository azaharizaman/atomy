<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for attributes table
 * 
 * Stores configurable product attributes (Color, Size, Material, etc.)
 * used for variant generation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            
            // Tenant scoping
            // $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            
            // Attribute identification
            $table->string('code', 100)->comment('Unique code within tenant (e.g., COLOR, SIZE)');
            $table->string('name');
            $table->text('description')->nullable();
            
            // Possible values stored as JSON array
            $table->json('values')->comment('Array of possible values for this attribute');
            
            // Display ordering
            $table->integer('sort_order')->default(0);
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            // $table->unique(['tenant_id', 'code'], 'attributes_tenant_code_unique');
            // $table->index(['tenant_id', 'is_active'], 'attributes_tenant_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
