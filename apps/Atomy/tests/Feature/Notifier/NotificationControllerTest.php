<?php

declare(strict_types=1);

namespace Tests\Feature\Notifier;

use App\Models\NotificationQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_send_a_notification(): void
    {
        $response = $this->postJson('/api/notifications/send', [
            'recipient_id' => 'user-123',
            'title' => 'Test Notification',
            'body' => 'This is a test notification body',
            'priority' => 'normal',
            'category' => 'system',
            'channels' => ['email', 'in_app'],
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'notification_id',
            'message',
        ]);

        // Verify notification was queued
        $this->assertDatabaseCount('notification_queue', 2); // One per channel
    }

    #[Test]
    public function it_validates_required_fields_when_sending(): void
    {
        $response = $this->postJson('/api/notifications/send', [
            'recipient_id' => 'user-123',
            // Missing title, body, priority
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'body', 'priority']);
    }

    #[Test]
    public function it_validates_priority_enum(): void
    {
        $response = $this->postJson('/api/notifications/send', [
            'recipient_id' => 'user-123',
            'title' => 'Test',
            'body' => 'Test body',
            'priority' => 'invalid-priority',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['priority']);
    }

    #[Test]
    public function it_can_send_batch_notifications(): void
    {
        $response = $this->postJson('/api/notifications/send-batch', [
            'recipient_ids' => ['user-1', 'user-2', 'user-3'],
            'title' => 'Batch Notification',
            'body' => 'This is sent to multiple recipients',
            'priority' => 'high',
            'category' => 'marketing',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'count' => 3,
        ]);
        $response->assertJsonStructure([
            'success',
            'notification_ids',
            'count',
            'message',
        ]);
    }

    #[Test]
    public function it_can_schedule_a_notification(): void
    {
        $scheduledAt = now()->addHours(2)->toIso8601String();

        $response = $this->postJson('/api/notifications/schedule', [
            'recipient_id' => 'user-123',
            'title' => 'Scheduled Notification',
            'body' => 'This will be sent later',
            'priority' => 'normal',
            'scheduled_at' => $scheduledAt,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'notification_id',
            'scheduled_at',
            'message',
        ]);

        // Verify notification was queued with scheduled time
        $this->assertDatabaseHas('notification_queue', [
            'status' => 'pending',
        ]);
    }

    #[Test]
    public function it_validates_scheduled_time_is_in_future(): void
    {
        $response = $this->postJson('/api/notifications/schedule', [
            'recipient_id' => 'user-123',
            'title' => 'Test',
            'body' => 'Test',
            'priority' => 'normal',
            'scheduled_at' => now()->subHour()->toIso8601String(), // Past time
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['scheduled_at']);
    }

    #[Test]
    public function it_can_cancel_a_scheduled_notification(): void
    {
        // First, create a queued notification
        $queueItem = NotificationQueue::create([
            'notification_id' => 'notif-123',
            'recipient_id' => 'user-123',
            'channel' => 'email',
            'priority' => 'normal',
            'status' => 'pending',
            'content' => ['title' => 'Test', 'body' => 'Test'],
            'scheduled_at' => now()->addHour(),
        ]);

        $response = $this->deleteJson("/api/notifications/{$queueItem->notification_id}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Verify notification was cancelled
        $this->assertDatabaseHas('notification_queue', [
            'id' => $queueItem->id,
            'status' => 'cancelled',
        ]);
    }

    #[Test]
    public function it_returns_404_when_cancelling_nonexistent_notification(): void
    {
        $response = $this->deleteJson('/api/notifications/nonexistent-id');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
        ]);
    }

    #[Test]
    public function it_can_get_notification_status(): void
    {
        $queueItem = NotificationQueue::create([
            'notification_id' => 'notif-456',
            'recipient_id' => 'user-123',
            'channel' => 'email',
            'priority' => 'normal',
            'status' => 'sent',
            'content' => ['title' => 'Test', 'body' => 'Test'],
            'sent_at' => now(),
        ]);

        $response = $this->getJson("/api/notifications/{$queueItem->notification_id}/status");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'notification_id' => 'notif-456',
            'status' => 'sent',
        ]);
    }
}
