<?php

declare(strict_types=1);

namespace App\Services\Channels;

use App\Models\InAppNotification;
use Nexus\Notifier\Contracts\NotifiableInterface;
use Nexus\Notifier\Contracts\NotificationChannelInterface;
use Nexus\Notifier\Contracts\NotificationInterface;
use Nexus\Notifier\Exceptions\DeliveryFailedException;
use Nexus\Notifier\ValueObjects\ChannelType;
use Nexus\Notifier\ValueObjects\DeliveryStatus;
use Psr\Log\LoggerInterface;

/**
 * In-App Notification Channel Implementation
 *
 * Stores notifications in database for display within the application.
 */
final readonly class InAppChannel implements NotificationChannelInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function send(
        NotifiableInterface $recipient,
        NotificationInterface $notification,
        array $content
    ): string {
        try {
            $this->logger->info('Creating in-app notification', [
                'recipient' => $recipient->getNotificationIdentifier(),
                'title' => $content['title'] ?? 'Notification',
            ]);

            // Store in database
            $inAppNotification = InAppNotification::create([
                'recipient_id' => $recipient->getNotificationIdentifier(),
                'title' => $content['title'] ?? 'Notification',
                'message' => $content['message'] ?? '',
                'link' => $content['link'] ?? null,
                'icon' => $content['icon'] ?? null,
                'priority' => $notification->getPriority()->value,
                'category' => $notification->getCategory()->value,
                'is_read' => false,
                'read_at' => null,
            ]);

            $this->logger->info('In-app notification created successfully', [
                'recipient' => $recipient->getNotificationIdentifier(),
                'notification_id' => $inAppNotification->id,
            ]);

            return $inAppNotification->id;
        } catch (\Throwable $e) {
            $this->logger->error('In-app notification failed', [
                'recipient' => $recipient->getNotificationIdentifier(),
                'error' => $e->getMessage(),
            ]);

            throw DeliveryFailedException::forChannel('in_app', $e->getMessage(), $e);
        }
    }

    public function supports(NotificationInterface $notification): bool
    {
        $content = $notification->getContent();
        return $content->hasContentFor(ChannelType::InApp);
    }

    public function getChannelType(): ChannelType
    {
        return ChannelType::InApp;
    }

    public function getDeliveryStatus(string $trackingId): DeliveryStatus
    {
        try {
            $notification = InAppNotification::find($trackingId);
            
            if (!$notification) {
                return DeliveryStatus::Failed;
            }

            // In-app notifications are immediately delivered when created
            return DeliveryStatus::Delivered;
        } catch (\Throwable) {
            return DeliveryStatus::Failed;
        }
    }

    public function isAvailable(): bool
    {
        // In-app channel is always available (no external dependencies)
        return true;
    }
}
