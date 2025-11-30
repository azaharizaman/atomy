<?php

declare(strict_types=1);

namespace Nexus\Notifier\Services;

use DateTimeImmutable;
use Nexus\Notifier\Contracts\NotifiableInterface;
use Nexus\Notifier\Contracts\NotificationChannelInterface;
use Nexus\Notifier\Contracts\NotificationHistoryRepositoryInterface;
use Nexus\Notifier\Contracts\NotificationInterface;
use Nexus\Notifier\Contracts\NotificationManagerInterface;
use Nexus\Notifier\Contracts\NotificationPreferenceRepositoryInterface;
use Nexus\Notifier\Contracts\NotificationQueueInterface;
use Nexus\Notifier\Exceptions\InvalidChannelException;
use Nexus\Notifier\Exceptions\InvalidRecipientException;
use Nexus\Notifier\ValueObjects\ChannelType;
use Nexus\Notifier\ValueObjects\DeliveryStatus;
use Psr\Log\LoggerInterface;

/**
 * Notification Manager
 *
 * Primary service for sending notifications across multiple channels.
 * Implements channel routing, preference checking, and queue management.
 */
final readonly class NotificationManager implements NotificationManagerInterface
{
    /**
     * @param array<NotificationChannelInterface> $channels
     */
    public function __construct(
        private array $channels,
        private NotificationQueueInterface $queue,
        private NotificationHistoryRepositoryInterface $history,
        private NotificationPreferenceRepositoryInterface $preferences,
        private LoggerInterface $logger
    ) {}

    public function send(
        NotifiableInterface $recipient,
        NotificationInterface $notification,
        ?array $channels = null
    ): string {
        $this->logger->info('Sending notification', [
            'recipient' => $recipient->getNotificationIdentifier(),
            'type' => $notification->getType(),
            'priority' => $notification->getPriority()->value,
        ]);

        // Generate notification ID
        $notificationId = $this->generateNotificationId();

        // Determine which channels to use
        $content = $notification->getContent();
        $targetChannels = $this->resolveChannels($recipient, $notification, $channels);

        if (empty($targetChannels)) {
            throw InvalidRecipientException::missingContactInfo(
                $recipient->getNotificationIdentifier(),
                'any'
            );
        }

        // Check if notification is allowed based on preferences
        foreach ($targetChannels as $channelType) {
            if (!$this->canSendViaChannel($recipient, $notification, $channelType)) {
                $this->logger->info('Skipping channel due to preferences', [
                    'recipient' => $recipient->getNotificationIdentifier(),
                    'channel' => $channelType->value,
                ]);
                continue;
            }

            // Queue notification for this channel
            $this->queueNotification(
                $notificationId,
                $recipient,
                $notification,
                $channelType
            );
        }

        // Log to history
        $this->history->log([
            'notification_id' => $notificationId,
            'recipient_id' => $recipient->getNotificationIdentifier(),
            'type' => $notification->getType(),
            'priority' => $notification->getPriority()->value,
            'category' => $notification->getCategory()->value,
            'status' => DeliveryStatus::Queued->value,
            'channels' => array_map(fn($c) => $c->value, $targetChannels),
            'created_at' => new DateTimeImmutable(),
        ]);

        return $notificationId;
    }

    public function sendBatch(
        array $recipients,
        NotificationInterface $notification,
        ?array $channels = null
    ): array {
        $trackingIds = [];

        foreach ($recipients as $recipient) {
            if (!$recipient instanceof NotifiableInterface) {
                continue;
            }

            try {
                $trackingIds[$recipient->getNotificationIdentifier()] = $this->send(
                    $recipient,
                    $notification,
                    $channels
                );
            } catch (\Exception $e) {
                $this->logger->error('Batch send failed for recipient', [
                    'recipient' => $recipient->getNotificationIdentifier(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $trackingIds;
    }

    public function schedule(
        NotifiableInterface $recipient,
        NotificationInterface $notification,
        DateTimeImmutable $scheduledAt,
        ?array $channels = null
    ): string {
        $this->logger->info('Scheduling notification', [
            'recipient' => $recipient->getNotificationIdentifier(),
            'type' => $notification->getType(),
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
        ]);

        $notificationId = $this->generateNotificationId();
        $targetChannels = $this->resolveChannels($recipient, $notification, $channels);

        foreach ($targetChannels as $channelType) {
            $this->queueNotification(
                $notificationId,
                $recipient,
                $notification,
                $channelType,
                $scheduledAt
            );
        }

        $this->history->log([
            'notification_id' => $notificationId,
            'recipient_id' => $recipient->getNotificationIdentifier(),
            'type' => $notification->getType(),
            'status' => DeliveryStatus::Pending->value,
            'scheduled_at' => $scheduledAt,
            'created_at' => new DateTimeImmutable(),
        ]);

        return $notificationId;
    }

    public function cancel(string $notificationId): bool
    {
        $this->logger->info('Cancelling notification', [
            'notification_id' => $notificationId,
        ]);

        $removed = $this->queue->remove($notificationId);

        if ($removed) {
            $this->history->updateStatus(
                $notificationId,
                DeliveryStatus::Cancelled,
                ['cancelled_at' => new DateTimeImmutable()]
            );
        }

        return $removed;
    }

    public function getStatus(string $notificationId): array
    {
        // Retrieve from history repository
        $history = $this->history->findByRecipient($notificationId, 1);

        if (empty($history)) {
            return [
                'status' => 'not_found',
                'channels' => [],
            ];
        }

        return [
            'status' => $history[0]['status'] ?? 'unknown',
            'channels' => $history[0]['channels'] ?? [],
        ];
    }

    /**
     * Resolve which channels to use for notification
     *
     * @param array<string>|null $requestedChannels
     * @return array<ChannelType>
     */
    private function resolveChannels(
        NotifiableInterface $recipient,
        NotificationInterface $notification,
        ?array $requestedChannels
    ): array {
        $content = $notification->getContent();
        $availableChannels = [];

        // If specific channels requested, use those
        if ($requestedChannels !== null) {
            foreach ($requestedChannels as $channelName) {
                try {
                    $channelType = ChannelType::from($channelName);
                    if ($content->hasContentFor($channelType)) {
                        $availableChannels[] = $channelType;
                    }
                } catch (\ValueError) {
                    throw InvalidChannelException::forChannel($channelName);
                }
            }
            return $availableChannels;
        }

        // Otherwise, use recipient preferences
        $preferences = $this->preferences->getPreferences($recipient->getNotificationIdentifier());
        $preferredChannels = $preferences['channels'] ?? [];

        foreach ($content->getAvailableChannels() as $channelType) {
            // Check if recipient has preferred this channel or no preference set (default to all)
            if (empty($preferredChannels) || in_array($channelType->value, $preferredChannels, true)) {
                $availableChannels[] = $channelType;
            }
        }

        return $availableChannels;
    }

    /**
     * Check if notification can be sent via channel based on preferences
     */
    private function canSendViaChannel(
        NotifiableInterface $recipient,
        NotificationInterface $notification,
        ChannelType $channel
    ): bool {
        return $this->preferences->canSendToRecipient(
            $recipient->getNotificationIdentifier(),
            $notification->getCategory(),
            $channel
        );
    }

    /**
     * Queue notification for delivery
     */
    private function queueNotification(
        string $notificationId,
        NotifiableInterface $recipient,
        NotificationInterface $notification,
        ChannelType $channelType,
        ?DateTimeImmutable $scheduledAt = null
    ): void {
        $content = $notification->getContent();

        $this->queue->enqueue([
            'notification_id' => $notificationId,
            'recipient_id' => $recipient->getNotificationIdentifier(),
            'recipient_data' => $this->extractRecipientData($recipient),
            'notification_type' => $notification->getType(),
            'channel' => $channelType->value,
            'content' => $content->getContentFor($channelType),
            'priority' => $notification->getPriority()->value,
            'category' => $notification->getCategory()->value,
        ], $scheduledAt);
    }

    /**
     * Extract recipient data for delivery
     *
     * @return array<string, mixed>
     */
    private function extractRecipientData(NotifiableInterface $recipient): array
    {
        return [
            'identifier' => $recipient->getNotificationIdentifier(),
            'email' => $recipient->getNotificationEmail(),
            'phone' => $recipient->getNotificationPhone(),
            'device_tokens' => $recipient->getNotificationDeviceTokens(),
            'locale' => $recipient->getNotificationLocale(),
            'timezone' => $recipient->getNotificationTimezone(),
        ];
    }

    /**
     * Generate unique notification ID
     */
    private function generateNotificationId(): string
    {
        return sprintf(
            'notif_%s_%s',
            (new \DateTimeImmutable())->format('YmdHis'),
            bin2hex(random_bytes(8))
        );
    }
}
