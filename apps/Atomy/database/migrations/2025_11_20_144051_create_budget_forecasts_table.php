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
        Schema::create('budget_forecasts', function (Blueprint $table) {
            // Primary key (ULID)
            $table->string('id', 26)->primary();
            
            // Forecast context
            $table->string('budget_id', 26)->index();
            $table->string('period_id', 26)->index();
            
            // Forecast predictions
            $table->decimal('projected_spending', 19, 4);
            $table->decimal('projected_variance', 19, 4);
            $table->decimal('overrun_probability', 5, 2); // 0-100%
            
            // Confidence intervals
            $table->decimal('confidence_lower_bound', 19, 4);
            $table->decimal('confidence_upper_bound', 19, 4);
            $table->decimal('certainty_score', 5, 2); // 0-100%
            
            // Model metadata
            $table->string('model_version', 20)->nullable();
            $table->json('model_features')->nullable(); // Features used for prediction
            
            // Forecast validity
            $table->timestamp('forecast_date');
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Audit
            $table->string('generated_by', 26)->nullable(); // User or 'system'
            $table->timestamps();
            
            // Indexes
            // $table->index(['budget_id', 'is_active']);
            // $table->index('forecast_date');
            
            // Foreign keys
            // // $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_forecasts');
    }
};
