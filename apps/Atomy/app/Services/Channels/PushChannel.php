<?php

declare(strict_types=1);

namespace App\Services\Channels;

use Nexus\Connector\Contracts\NotificationServiceConnectorInterface;
use Nexus\Notifier\Contracts\NotifiableInterface;
use Nexus\Notifier\Contracts\NotificationChannelInterface;
use Nexus\Notifier\Contracts\NotificationInterface;
use Nexus\Notifier\Exceptions\DeliveryFailedException;
use Nexus\Notifier\Exceptions\InvalidRecipientException;
use Nexus\Notifier\ValueObjects\ChannelType;
use Nexus\Notifier\ValueObjects\DeliveryStatus;
use Psr\Log\LoggerInterface;

/**
 * Push Notification Channel Implementation
 *
 * Sends push notifications via FCM/APNs using the Connector package.
 */
final readonly class PushChannel implements NotificationChannelInterface
{
    public function __construct(
        private NotificationServiceConnectorInterface $pushConnector,
        private LoggerInterface $logger
    ) {}

    public function send(
        NotifiableInterface $recipient,
        NotificationInterface $notification,
        array $content
    ): string {
        $deviceTokens = $recipient->getNotificationDeviceTokens();

        if (empty($deviceTokens)) {
            throw InvalidRecipientException::missingContactInfo(
                $recipient->getNotificationIdentifier(),
                'push'
            );
        }

        try {
            $this->logger->info('Sending push notification', [
                'recipient' => $recipient->getNotificationIdentifier(),
                'device_count' => count($deviceTokens),
                'title' => $content['title'] ?? 'Notification',
            ]);

            // Send to all device tokens
            $trackingIds = [];
            foreach ($deviceTokens as $token) {
                $trackingId = $this->pushConnector->sendPushNotification(
                    deviceToken: $token,
                    title: $content['title'] ?? 'Notification',
                    body: $content['body'] ?? '',
                    data: [
                        'action' => $content['action'] ?? null,
                        'icon' => $content['icon'] ?? null,
                    ]
                );
                $trackingIds[] = $trackingId;
            }

            // Return comma-separated tracking IDs
            $combinedTrackingId = implode(',', $trackingIds);

            $this->logger->info('Push notification sent successfully', [
                'recipient' => $recipient->getNotificationIdentifier(),
                'tracking_id' => $combinedTrackingId,
            ]);

            return $combinedTrackingId;
        } catch (\Throwable $e) {
            $this->logger->error('Push notification failed', [
                'recipient' => $recipient->getNotificationIdentifier(),
                'error' => $e->getMessage(),
            ]);

            throw DeliveryFailedException::forChannel('push', $e->getMessage(), $e);
        }
    }

    public function supports(NotificationInterface $notification): bool
    {
        $content = $notification->getContent();
        return $content->hasContentFor(ChannelType::Push);
    }

    public function getChannelType(): ChannelType
    {
        return ChannelType::Push;
    }

    public function getDeliveryStatus(string $trackingId): DeliveryStatus
    {
        try {
            // Handle multiple tracking IDs
            $trackingIds = explode(',', $trackingId);
            $allDelivered = true;
            $anyFailed = false;

            foreach ($trackingIds as $id) {
                $status = $this->pushConnector->getDeliveryStatus(trim($id));
                if ($status !== 'delivered') {
                    $allDelivered = false;
                }
                if ($status === 'failed') {
                    $anyFailed = true;
                }
            }

            if ($anyFailed) {
                return DeliveryStatus::Failed;
            }
            if ($allDelivered) {
                return DeliveryStatus::Delivered;
            }

            return DeliveryStatus::Sent;
        } catch (\Throwable) {
            return DeliveryStatus::Pending;
        }
    }

    public function isAvailable(): bool
    {
        try {
            return $this->pushConnector->isHealthy();
        } catch (\Throwable) {
            return false;
        }
    }
}
