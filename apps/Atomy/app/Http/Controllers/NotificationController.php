<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nexus\Notifier\Contracts\NotificationInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Notifier\Services\AbstractNotification;
use Nexus\Notifier\ValueObjects\Category;
use Nexus\Notifier\ValueObjects\NotificationContent;
use Nexus\Notifier\ValueObjects\Priority;
use Psr\Log\LoggerInterface;

final class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationManagerInterface $notificationManager,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Send a notification to a single recipient.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_id' => 'required|string',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'priority' => 'required|string|in:low,normal,high,critical',
            'category' => 'nullable|string|in:system,marketing,transactional,security',
            'channels' => 'nullable|array',
            'channels.*' => 'string|in:email,sms,push,in_app',
            'data' => 'nullable|array',
            'action_url' => 'nullable|url',
        ]);

        try {
            $notification = $this->createNotification($validated);

            $notificationId = $this->notificationManager->send(
                notifiable: new SimpleNotifiable($validated['recipient_id']),
                notification: $notification
            );

            return response()->json([
                'success' => true,
                'notification_id' => $notificationId,
                'message' => 'Notification sent successfully',
            ], 201);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to send notification', [
                'recipient' => $validated['recipient_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send notifications to multiple recipients.
     */
    public function sendBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'string',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'priority' => 'required|string|in:low,normal,high,critical',
            'category' => 'nullable|string|in:system,marketing,transactional,security',
            'channels' => 'nullable|array',
            'channels.*' => 'string|in:email,sms,push,in_app',
            'data' => 'nullable|array',
            'action_url' => 'nullable|url',
        ]);

        try {
            $notification = $this->createNotification($validated);

            $notifiables = array_map(
                fn(string $id) => new SimpleNotifiable($id),
                $validated['recipient_ids']
            );

            $notificationIds = $this->notificationManager->sendBatch(
                notifiables: $notifiables,
                notification: $notification
            );

            return response()->json([
                'success' => true,
                'notification_ids' => $notificationIds,
                'count' => count($notificationIds),
                'message' => 'Notifications sent successfully',
            ], 201);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to send batch notifications', [
                'recipient_count' => count($validated['recipient_ids']),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send batch notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Schedule a notification for later delivery.
     */
    public function schedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_id' => 'required|string',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'priority' => 'required|string|in:low,normal,high,critical',
            'category' => 'nullable|string|in:system,marketing,transactional,security',
            'channels' => 'nullable|array',
            'channels.*' => 'string|in:email,sms,push,in_app',
            'data' => 'nullable|array',
            'action_url' => 'nullable|url',
            'scheduled_at' => 'required|date|after:now',
        ]);

        try {
            $notification = $this->createNotification($validated);
            $scheduledAt = new \DateTimeImmutable($validated['scheduled_at']);

            $notificationId = $this->notificationManager->schedule(
                notifiable: new SimpleNotifiable($validated['recipient_id']),
                notification: $notification,
                sendAt: $scheduledAt
            );

            return response()->json([
                'success' => true,
                'notification_id' => $notificationId,
                'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
                'message' => 'Notification scheduled successfully',
            ], 201);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to schedule notification', [
                'recipient' => $validated['recipient_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a scheduled notification.
     */
    public function cancel(string $notificationId): JsonResponse
    {
        try {
            $cancelled = $this->notificationManager->cancel($notificationId);

            if ($cancelled) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification cancelled successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Notification not found or already sent',
            ], 404);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to cancel notification', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get notification delivery status.
     */
    public function status(string $notificationId): JsonResponse
    {
        try {
            $status = $this->notificationManager->getStatus($notificationId);

            if ($status === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'notification_id' => $notificationId,
                'status' => $status->value,
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to get notification status', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a notification from validated data.
     */
    private function createNotification(array $data): NotificationInterface
    {
        return new class(
            Priority::from($data['priority']),
            isset($data['category']) ? Category::from($data['category']) : Category::System,
            new NotificationContent(
                title: $data['title'],
                body: $data['body'],
                data: $data['data'] ?? [],
                actionUrl: $data['action_url'] ?? null
            ),
            $data['channels'] ?? null
        ) extends AbstractNotification {
            public function __construct(
                private readonly Priority $priority,
                private readonly Category $category,
                private readonly NotificationContent $content,
                private readonly ?array $channels
            ) {}

            public function getPriority(): Priority
            {
                return $this->priority;
            }

            public function getCategory(): Category
            {
                return $this->category;
            }

            public function getContent(): NotificationContent
            {
                return $this->content;
            }

            public function getChannels(): ?array
            {
                return $this->channels;
            }
        };
    }
}

/**
 * Simple implementation of NotifiableInterface for ad-hoc notifications.
 */
final class SimpleNotifiable implements \Nexus\Notifier\Contracts\NotifiableInterface
{
    public function __construct(
        private readonly string $id
    ) {}

    public function getNotifiableId(): string
    {
        return $this->id;
    }
}
