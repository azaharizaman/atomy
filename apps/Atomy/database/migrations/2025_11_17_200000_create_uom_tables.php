<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates tables for Unit of Measurement system:
     * - dimensions: Categories of measurement (Mass, Length, etc.)
     * - unit_systems: Systems like Metric, Imperial
     * - units: Individual units with their properties
     * - unit_conversions: Conversion rules between units
     */
    public function up(): void
    {
        // Dimensions table
        Schema::create('dimensions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('base_unit_code', 50);
            $table->boolean('allows_offset')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_system_defined')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // // $table->index('code');
        });

        // Unit systems table
        Schema::create('unit_systems', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_system_defined')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // // $table->index('code');
        });

        // Units table
        Schema::create('units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('symbol', 20);
            $table->string('dimension_code', 50);
            $table->string('system_code', 50)->nullable();
            $table->boolean('is_base_unit')->default(false);
            $table->boolean('is_system_unit')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // // $table->index('code');
            // // $table->index('dimension_code');
            // // $table->index('system_code');
            // // $table->index(['dimension_code', 'is_base_unit']);

            // // $table->foreign('dimension_code')
                // // ->references('code')
                // // ->on('dimensions')
                // // ->onDelete('restrict');

            // // $table->foreign('system_code')
                // // ->references('code')
                // // ->on('unit_systems')
                // // ->onDelete('set null');
        });

        // Unit conversions table
        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('from_unit_code', 50);
            $table->string('to_unit_code', 50);
            $table->decimal('ratio', 30, 15); // High precision for conversions
            $table->decimal('offset', 30, 15)->default(0.0);
            $table->boolean('is_bidirectional')->default(true);
            $table->integer('version')->default(1); // For audit trail
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // // $table->index(['from_unit_code', 'to_unit_code']);
            // // $table->index('to_unit_code');
            // // $table->unique(['from_unit_code', 'to_unit_code', 'deleted_at']);

            // // $table->foreign('from_unit_code')
                // // ->references('code')
                // // ->on('units')
                // // ->onDelete('cascade');

            // // $table->foreign('to_unit_code')
                // // ->references('code')
                // // ->on('units')
                // // ->onDelete('cascade');
        });

        // Update base_unit_code foreign key on dimensions after units table created
        Schema::table('dimensions', function (Blueprint $table) {
            // // $table->foreign('base_unit_code')
                // // ->references('code')
                // // ->on('units')
                // // ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dimensions', function (Blueprint $table) {
            $table->dropForeign(['base_unit_code']);
        });

        Schema::dropIfExists('unit_conversions');
        Schema::dropIfExists('units');
        Schema::dropIfExists('unit_systems');
        Schema::dropIfExists('dimensions');
    }
};
