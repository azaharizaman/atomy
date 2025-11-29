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
        Schema::create('backoffice_supervisors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('staff_id')->index();
            $table->ulid('supervisor_id')->index();
            $table->string('type')->default('direct');
            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('backoffice_staff')->cascadeOnDelete();
            $table->foreign('supervisor_id')->references('id')->on('backoffice_staff')->cascadeOnDelete();
            $table->unique(['staff_id', 'supervisor_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backoffice_supervisors');
    }
};
