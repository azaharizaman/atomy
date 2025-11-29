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
        Schema::create('backoffice_companies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('code')->index();
            $table->string('name');
            $table->string('registration_number')->nullable();
            $table->date('registration_date')->nullable();
            $table->string('jurisdiction')->nullable();
            $table->string('status')->default('active')->index();
            $table->ulid('parent_company_id')->nullable()->index();
            $table->integer('financial_year_start_month')->nullable();
            $table->string('industry')->nullable();
            $table->string('size')->nullable();
            $table->string('tax_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->foreign('parent_company_id')->references('id')->on('backoffice_companies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backoffice_companies');
    }
};
