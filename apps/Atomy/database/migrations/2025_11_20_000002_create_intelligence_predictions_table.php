<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intelligence_predictions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('job_id', 50)->nullable()->index();
            // $table->foreignUlid('model_id')->constrained('intelligence_models')->onDelete('cascade');
            $table->string('model_version', 20)->nullable();
            $table->json('features_json');
            $table->string('features_hash', 64)->index();
            $table->json('result_json');
            $table->decimal('raw_confidence', 5, 4)->nullable();
            $table->decimal('calibrated_confidence', 5, 4)->nullable();
            $table->json('feature_importance_json')->nullable();
            $table->boolean('requires_review')->default(false)->index();
            $table->boolean('is_adversarial')->default(false);
            $table->string('status', 20)->default('completed'); // pending, processing, completed, failed
            $table->boolean('actual_outcome')->nullable(); // for calibration training
            $table->integer('deployment_age_hours')->nullable(); // for rollback tracking
            $table->timestamps();

            // $table->index(['model_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intelligence_predictions');
    }
};
