<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisition_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // $table->foreignUlid('requisition_id')->constrained('requisitions')->onDelete('cascade');
            $table->integer('line_number');
            $table->string('item_code')->index();
            $table->text('description');
            $table->decimal('quantity', 19, 4);
            $table->string('unit');
            $table->decimal('estimated_unit_price', 19, 4);
            $table->decimal('line_total', 19, 4);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // $table->index(['requisition_id', 'line_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisition_lines');
    }
};
