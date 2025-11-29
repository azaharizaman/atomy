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
        Schema::create('backoffice_units', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('company_id', 26)->index();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->string('status');
            $table->string('leader_staff_id', 26)->nullable();
            $table->string('deputy_leader_staff_id', 26)->nullable();
            $table->text('purpose')->nullable();
            $table->text('objectives')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('backoffice_companies')->onDelete('cascade');
            $table->foreign('leader_staff_id')->references('id')->on('backoffice_staff')->onDelete('set null');
            $table->foreign('deputy_leader_staff_id')->references('id')->on('backoffice_staff')->onDelete('set null');
            
            $table->unique(['company_id', 'code']);
        });

        Schema::create('backoffice_unit_members', function (Blueprint $table) {
            $table->string('id', 26)->primary();
            $table->string('unit_id', 26)->index();
            $table->string('staff_id', 26)->index();
            $table->string('role');
            $table->timestamps();

            $table->foreign('unit_id')->references('id')->on('backoffice_units')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('backoffice_staff')->onDelete('cascade');

            $table->unique(['unit_id', 'staff_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backoffice_unit_members');
        Schema::dropIfExists('backoffice_units');
    }
};
