<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intelligence_usage', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26);
            $table->string('model_name');
            $table->string('domain_context', 50); // procurement, sales, finance, etc.
            $table->bigInteger('tokens_used')->default(0);
            $table->integer('api_calls')->default(0);
            $table->decimal('api_cost', 10, 6)->default(0); // USD
            $table->string('period_month', 7); // YYYY-MM
            $table->timestamp('created_at');

            // Composite index for efficient aggregation queries
            $table->index(['tenant_id', 'period_month', 'model_name', 'domain_context'], 'usage_aggregate_idx');
            $table->index(['period_month', 'model_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intelligence_usage');
    }
};
