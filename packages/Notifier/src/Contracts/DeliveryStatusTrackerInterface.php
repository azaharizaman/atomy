<?php

declare(strict_types=1);

namespace Nexus\Notifier\Contracts;

use Nexus\Notifier\ValueObjects\DeliveryStatus;

/**
 * Delivery Status Tracker Interface
 *
 * Tracks delivery status updates from external providers via webhooks.
 */
interface DeliveryStatusTrackerInterface
{
    /**
     * Track delivery status for a notification
     *
     * @param string $notificationId
     * @param string $channel Channel identifier
     * @param DeliveryStatus $status
     * @param array<string, mixed> $metadata Additional tracking data
     * @return void
     */
    public function track(
        string $notificationId,
        string $channel,
        DeliveryStatus $status,
        array $metadata = []
    ): void;

    /**
     * Get current delivery status
     *
     * @param string $notificationId
     * @return array{status: DeliveryStatus, channels: array<string, DeliveryStatus>, updated_at: \DateTimeImmutable}
     */
    public function getStatus(string $notificationId): array;

    /**
     * Handle webhook callback from external provider
     *
     * @param string $provider Provider identifier (e.g., 'twilio', 'sendgrid')
     * @param array<string, mixed> $payload Webhook payload
     * @return bool True if processed successfully
     */
    public function handleWebhook(string $provider, array $payload): bool;

    /**
     * Get delivery statistics for a time period
     *
     * @param \DateTimeImmutable $startDate
     * @param \DateTimeImmutable $endDate
     * @return array{total: int, delivered: int, failed: int, bounced: int, pending: int}
     */
    public function getStatistics(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array;
}
