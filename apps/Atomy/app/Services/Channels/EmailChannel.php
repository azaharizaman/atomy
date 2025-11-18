<?php

declare(strict_types=1);

namespace App\Services\Channels;

use Nexus\Connector\Contracts\EmailServiceConnectorInterface;
use Nexus\Notifier\Contracts\NotifiableInterface;
use Nexus\Notifier\Contracts\NotificationChannelInterface;
use Nexus\Notifier\Contracts\NotificationInterface;
use Nexus\Notifier\Exceptions\DeliveryFailedException;
use Nexus\Notifier\Exceptions\InvalidRecipientException;
use Nexus\Notifier\ValueObjects\ChannelType;
use Nexus\Notifier\ValueObjects\DeliveryStatus;
use Psr\Log\LoggerInterface;

/**
 * Email Channel Implementation
 *
 * Sends notifications via email using the Connector package.
 */
final readonly class EmailChannel implements NotificationChannelInterface
{
    public function __construct(
        private EmailServiceConnectorInterface $emailConnector,
        private LoggerInterface $logger
    ) {}

    public function send(
        NotifiableInterface $recipient,
        NotificationInterface $notification,
        array $content
    ): string {
        $email = $recipient->getNotificationEmail();

        if (!$email) {
            throw InvalidRecipientException::missingContactInfo(
                $recipient->getNotificationIdentifier(),
                'email'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw InvalidRecipientException::invalidEmail($email);
        }

        try {
            $this->logger->info('Sending email notification', [
                'recipient' => $email,
                'subject' => $content['subject'] ?? 'Notification',
            ]);

            // Send via Connector
            $trackingId = $this->emailConnector->sendTransactionalEmail(
                to: $email,
                subject: $content['subject'] ?? 'Notification',
                body: $content['body'] ?? '',
                attachments: $content['attachments'] ?? []
            );

            $this->logger->info('Email notification sent successfully', [
                'recipient' => $email,
                'tracking_id' => $trackingId,
            ]);

            return $trackingId;
        } catch (\Throwable $e) {
            $this->logger->error('Email notification failed', [
                'recipient' => $email,
                'error' => $e->getMessage(),
            ]);

            throw DeliveryFailedException::forChannel('email', $e->getMessage(), $e);
        }
    }

    public function supports(NotificationInterface $notification): bool
    {
        $content = $notification->getContent();
        return $content->hasContentFor(ChannelType::Email);
    }

    public function getChannelType(): ChannelType
    {
        return ChannelType::Email;
    }

    public function getDeliveryStatus(string $trackingId): DeliveryStatus
    {
        try {
            // Query connector for status
            $status = $this->emailConnector->getDeliveryStatus($trackingId);
            
            return match ($status) {
                'delivered' => DeliveryStatus::Delivered,
                'sent' => DeliveryStatus::Sent,
                'bounced' => DeliveryStatus::Bounced,
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
            return $this->emailConnector->isHealthy();
        } catch (\Throwable) {
            return false;
        }
    }
}
