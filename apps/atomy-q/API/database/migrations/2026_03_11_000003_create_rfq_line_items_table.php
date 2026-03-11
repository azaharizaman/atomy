<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfq_line_items', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('rfq_id');
            $table->text('description');
            $table->decimal('quantity', 15, 4);
            $table->string('uom');
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->text('specifications')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('rfq_id');
            $table->index(['tenant_id', 'rfq_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfq_line_items');
    }
};
