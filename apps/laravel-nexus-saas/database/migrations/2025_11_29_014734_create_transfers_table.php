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
        Schema::create('transfers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('staff_id')->index();
            $table->ulid('from_department_id')->nullable()->index();
            $table->ulid('to_department_id')->nullable()->index();
            $table->ulid('from_office_id')->nullable()->index();
            $table->ulid('to_office_id')->nullable()->index();
            $table->date('effective_date');
            $table->string('type');
            $table->string('reason')->nullable();
            $table->string('status')->default('pending');
            
            $table->string('requested_by')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            $table->string('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();
            
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('backoffice_staff')->cascadeOnDelete();
            $table->foreign('from_department_id')->references('id')->on('backoffice_departments')->nullOnDelete();
            $table->foreign('to_department_id')->references('id')->on('backoffice_departments')->cascadeOnDelete();
            $table->foreign('from_office_id')->references('id')->on('backoffice_offices')->nullOnDelete();
            $table->foreign('to_office_id')->references('id')->on('backoffice_offices')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
