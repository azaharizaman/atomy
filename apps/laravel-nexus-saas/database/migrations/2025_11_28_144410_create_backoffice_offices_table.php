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
        Schema::create('backoffice_offices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('company_id')->index();
            $table->string('code')->index();
            $table->string('name');
            $table->string('type')->index();
            $table->string('status')->default('active')->index();
            $table->ulid('parent_office_id')->nullable()->index();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('country');
            $table->string('postal_code');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('fax')->nullable();
            $table->string('timezone')->nullable();
            $table->string('operating_hours')->nullable();
            $table->integer('staff_capacity')->nullable();
            $table->decimal('floor_area', 10, 2)->nullable();
            $table->boolean('is_head_office')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->foreign('company_id')->references('id')->on('backoffice_companies')->cascadeOnDelete();
            $table->foreign('parent_office_id')->references('id')->on('backoffice_offices')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backoffice_offices');
    }
};
