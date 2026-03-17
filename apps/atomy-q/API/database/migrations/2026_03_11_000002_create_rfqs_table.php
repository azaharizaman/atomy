<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rfqs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->index();
            $table->string('rfq_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('department')->nullable();
            $table->string('status')->default('draft');
            $table->ulid('owner_id');
            $table->decimal('estimated_value', 15, 2)->default(0);
            $table->decimal('savings_percentage', 5, 2)->default(0);
            $table->timestamp('submission_deadline')->nullable();
            $table->timestamp('closing_date')->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('evaluation_method')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'owner_id']);
            $table->unique(['tenant_id', 'rfq_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rfqs');
    }
};
