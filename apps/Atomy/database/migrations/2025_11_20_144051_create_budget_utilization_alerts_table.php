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
        Schema::create('budget_utilization_alerts', function (Blueprint $table) {
            // Primary key (ULID)
            $table->string('id', 26)->primary();
            
            // Alert context
            $table->string('budget_id', 26)->index();
            $table->string('period_id', 26)->index();
            
            // Utilization metrics
            $table->decimal('utilization_percentage', 5, 2);
            $table->decimal('allocated_amount', 19, 4);
            $table->decimal('actual_amount', 19, 4);
            $table->decimal('committed_amount', 19, 4);
            $table->string('currency', 3);
            
            // Alert severity and type
            $table->string('severity', 20); // low, medium, high, critical
            $table->string('alert_type', 30)->default('utilization_threshold'); // utilization_threshold, variance, overrun
            
            // Alert message
            $table->text('message');
            
            // Alert state
            $table->boolean('is_acknowledged')->default(false);
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledged_by', 26)->nullable();
            $table->text('acknowledgement_notes')->nullable();
            
            // Notification tracking
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            $table->json('notification_channels')->nullable(); // ['email', 'slack', 'sms']
            
            // Audit
            $table->timestamp('triggered_at');
            $table->timestamps();
            
            // Indexes
            $table->index(['budget_id', 'is_acknowledged']);
            $table->index(['severity', 'is_acknowledged']);
            $table->index('triggered_at');
            
            // Foreign keys
            // $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_utilization_alerts');
    }
};
