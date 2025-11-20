<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intelligence_models', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id', 26)->index();
            $table->string('name')->index(); // e.g., 'procurement_po_qty_check'
            $table->string('type', 50); // anomaly_detection, prediction, etc.
            $table->string('provider', 50); // openai, anthropic, gemini
            $table->string('endpoint_url')->nullable();
            $table->string('custom_endpoint_url')->nullable(); // tenant-specific fine-tuned
            $table->string('current_version', 20)->nullable();
            $table->json('config_json')->nullable();
            $table->string('expected_feature_version', 20)->default('1.0');
            $table->decimal('baseline_confidence', 5, 4)->nullable();
            $table->decimal('drift_threshold', 5, 4)->default(0.15);
            $table->boolean('ab_test_enabled')->default(false);
            $table->string('ab_test_model_b', 50)->nullable();
            $table->decimal('ab_test_weight', 3, 2)->default(0.5);
            $table->boolean('calibration_enabled')->default(true);
            $table->boolean('adversarial_testing_enabled')->default(false);
            $table->boolean('cost_optimization_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intelligence_models');
    }
};
