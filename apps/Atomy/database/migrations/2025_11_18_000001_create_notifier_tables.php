<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('type')->unique(); // Notification type identifier
            $table->string('name');
            $table->string('category'); // transactional, marketing, system, alert
            $table->json('email_content')->nullable(); // {subject, body, attachments}
            $table->text('sms_content')->nullable();
            $table->json('push_content')->nullable(); // {title, body, action, icon}
            $table->json('in_app_content')->nullable(); // {title, message, link, icon}
            $table->json('variables')->nullable(); // Expected variables
            $table->string('locale')->default('en');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['type', 'locale']);
            $table->index('is_active');
        });

        Schema::create('notification_history', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('notification_id')->index(); // Tracking ID
            $table->string('recipient_id')->index();
            $table->string('notification_type');
            $table->string('channel'); // email, sms, push, in_app
            $table->string('priority'); // low, normal, high, critical
            $table->string('category'); // transactional, marketing, system, alert
            $table->string('status'); // pending, queued, sending, sent, delivered, failed, bounced, cancelled
            $table->json('content')->nullable();
            $table->json('recipient_data')->nullable();
            $table->json('metadata')->nullable(); // Provider response, error details, etc.
            $table->string('tracking_external_id')->nullable(); // External provider tracking ID
            $table->integer('retry_count')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            
            $table->index(['recipient_id', 'created_at']);
            $table->index(['notification_id', 'channel']);
            $table->index(['status', 'scheduled_at']);
            $table->index('created_at');
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('recipient_id')->unique();
            $table->json('preferred_channels')->nullable(); // [email, sms, push, in_app]
            $table->json('category_preferences')->nullable(); // {marketing: false, alert: true}
            $table->json('quiet_hours')->nullable(); // {start: '22:00', end: '08:00', timezone: 'UTC'}
            $table->boolean('global_opt_out')->default(false);
            $table->timestamps();
            
            $table->index('recipient_id');
        });

        Schema::create('notification_queue', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('notification_id')->index();
            $table->string('recipient_id')->index();
            $table->string('channel');
            $table->string('priority');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->json('payload'); // Complete notification data
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamps();
            
            $table->index(['status', 'scheduled_at', 'priority']);
            $table->index('notification_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_queue');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_history');
        Schema::dropIfExists('notification_templates');
    }
};
