<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // // $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type'); // earning, deduction, contribution
            $table->string('calculation_method'); // fixed_amount, percentage_of_basic, etc.
            $table->decimal('fixed_amount', 15, 2)->nullable();
            $table->string('percentage_of')->nullable(); // basic, gross, component
            $table->decimal('percentage_value', 5, 2)->nullable();
            $table->ulid('reference_component_id')->nullable()->index();
            $table->text('formula')->nullable();
            $table->boolean('is_statutory')->default(false);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // // $table->index(['tenant_id', 'type', 'is_active']);
            // // $table->index(['tenant_id', 'code']);
        });
        
        // Add self-referencing foreign key after table creation
        Schema::table('payroll_components', function (Blueprint $table) {
            // // $table->foreign('reference_component_id')
                // ->references('id')
                // ->on('payroll_components')
                // ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_components');
    }
};
