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
        Schema::create('backoffice_staff_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('staff_id')->index();
            $table->ulid('department_id')->index();
            $table->string('job_title');
            $table->boolean('is_primary')->default(false);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('backoffice_staff')->cascadeOnDelete();
            $table->foreign('department_id')->references('id')->on('backoffice_departments')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backoffice_staff_assignments');
    }
};
