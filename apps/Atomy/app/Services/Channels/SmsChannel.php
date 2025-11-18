<?php

declare(strict_types=1);

namespace App\Services\Channels;

use Nexus\Connector\Contracts\SmsServiceConnectorInterface;
use Nexus\Notifier\Contracts\NotifiableInterface;
use Nexus\Notifier\Contracts\NotificationChannelInterface;
use Nexus\Notifier\Contracts\NotificationInterface;
use Nexus\Notifier\Exceptions\DeliveryFailedException;
use Nexus\Notifier\Exceptions\InvalidRecipientException;
use Nexus\Notifier\ValueObjects\ChannelType;
use Nexus\Notifier\ValueObjects\DeliveryStatus;
use Psr\Log\LoggerInterface;

/**
 * SMS Channel Implementation
 *
 * Sends notifications via SMS using the Connector package.
 */
final readonly class SmsChannel implements NotificationChannelInterface
{
    public function __construct(
        private SmsServiceConnectorInterface $smsConnector,
        private LoggerInterface $logger
    ) {}

    public function send(
        NotifiableInterface $recipient,
        NotificationInterface $notification,
        array $content
    ): string {
        $phone = $recipient->getNotificationPhone();

        if (!$phone) {
            throw InvalidRecipientException::missingContactInfo(
                $recipient->getNotificationIdentifier(),
                'sms'
            );
        }

        // Basic phone validation
        if (!preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
            throw InvalidRecipientException::invalidPhone($phone);
        }

        try {
            $this->logger->info('Sending SMS notification', [
                'recipient' => $phone,
                'message_length' => strlen($content),
            ]);

            // Content should be a string for SMS
            $message = is_array($content) ? ($content['text'] ?? '') : $content;

            // Send via Connector
            $trackingId = $this->smsConnector->send(
                phone: $phone,
                message: $message
            );

            $this->logger->info('SMS notification sent successfully', [
                'recipient' => $phone,
                'tracking_id' => $trackingId,
            ]);

            return $trackingId;
        } catch (\Throwable $e) {
            $this->logger->error('SMS notification failed', [
                'recipient' => $phone,
                'error' => $e->getMessage(),
            ]);

            throw DeliveryFailedException::forChannel('sms', $e->getMessage(), $e);
        }
    }

    public function supports(NotificationInterface $notification): bool
    {
        $content = $notification->getContent();
        return $content->hasContentFor(ChannelType::Sms);
    }

    public function getChannelType(): ChannelType
    {
        return ChannelType::Sms;
    }

    public function getDeliveryStatus(string $trackingId): DeliveryStatus
    {
        try {
            $status = $this->smsConnector->getDeliveryStatus($trackingId);
            
            return match ($status) {
                'delivered' => DeliveryStatus::Delivered,
                'sent' => DeliveryStatus::Sent,
                'failed' => DeliveryStatus::Failed,
                default => DeliveryStatus::Pending,
            };
        } catch (\Throwable) {
            return DeliveryStatus::Pending;
        }
    }

    public function isAvailable(): bool
    {
        try {
            return $this->smsConnector->isHealthy();
        } catch (\Throwable) {
            return false;
        }
    }
}
