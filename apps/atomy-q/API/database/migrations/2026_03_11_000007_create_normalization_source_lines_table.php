<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('normalization_source_lines', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->ulid('quote_submission_id');
            $table->ulid('rfq_line_item_id')->nullable();
            $table->string('source_vendor')->nullable();
            $table->text('source_description')->nullable();
            $table->decimal('source_quantity', 15, 4)->nullable();
            $table->string('source_uom')->nullable();
            $table->decimal('source_unit_price', 15, 4)->nullable();
            $table->json('raw_data')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('quote_submission_id');
            $table->index(['tenant_id', 'quote_submission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('normalization_source_lines');
    }
};
