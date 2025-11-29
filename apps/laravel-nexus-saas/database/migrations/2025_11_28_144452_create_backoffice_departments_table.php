<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('backoffice_departments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('company_id')->index();
            $table->string('code')->index();
            $table->string('name');
            $table->string('type')->index();
            $table->string('status')->default('active')->index();
            $table->ulid('parent_department_id')->nullable()->index();
            $table->ulid('manager_staff_id')->nullable()->index();
            $table->string('cost_center')->nullable();
            $table->decimal('budget_amount', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->foreign('company_id')->references('id')->on('backoffice_companies')->cascadeOnDelete();
            $table->foreign('parent_department_id')->references('id')->on('backoffice_departments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backoffice_departments');
    }
};
