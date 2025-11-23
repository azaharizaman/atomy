<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bin_locations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('warehouse_id');
            $table->string('code', 50)->index();
            $table->string('aisle')->nullable();
            $table->string('rack')->nullable();
            $table->string('shelf')->nullable();
            $table->decimal('coordinates_latitude', 10, 7)->nullable();
            $table->decimal('coordinates_longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            // $table->unique(['warehouse_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bin_locations');
    }
};
