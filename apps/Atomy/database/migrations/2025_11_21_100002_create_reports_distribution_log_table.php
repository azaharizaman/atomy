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
        Schema::create('reports_distribution_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_generated_id'); // References reports_generated
            $table->uuid('recipient_id'); // NotifiableInterface ID
            $table->uuid('notification_id')->nullable(); // From Notifier package
            $table->string('channel_type', 20); // email, sms, in_app
            $table->string('status', 20)->default('pending'); // pending, sent, delivered, failed
            $table->timestamp('delivered_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('report_generated_id');
            $table->index('recipient_id');
            $table->index(['status', 'created_at']);
            $table->index('notification_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports_distribution_log');
    }
};
