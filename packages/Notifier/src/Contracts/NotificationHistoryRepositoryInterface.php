<?php

declare(strict_types=1);

namespace Nexus\Notifier\Contracts;

use Nexus\Notifier\ValueObjects\DeliveryStatus;
use DateTimeImmutable;

/**
 * Notification History Repository Interface
 *
 * Manages storage and retrieval of notification delivery history.
 */
interface NotificationHistoryRepositoryInterface
{
    /**
     * Log a notification send attempt
     *
     * @param array<string, mixed> $logData
     * @return string Log entry ID
     */
    public function log(array $logData): string;

    /**
     * Find notification history by recipient
     *
     * @param string $recipientId
     * @param int $limit
     * @return array<array<string, mixed>>
     */
    public function findByRecipient(string $recipientId, int $limit = 100): array;

    /**
     * Find notifications by delivery status
     *
     * @param DeliveryStatus $status
     * @param int $limit
     * @return array<array<string, mixed>>
     */
    public function findByStatus(DeliveryStatus $status, int $limit = 100): array;

    /**
     * Update delivery status
     *
     * @param string $notificationId
     * @param DeliveryStatus $status
     * @param array<string, mixed> $metadata Additional metadata
     * @return bool True if updated successfully
     */
    public function updateStatus(string $notificationId, DeliveryStatus $status, array $metadata = []): bool;

    /**
     * Get notifications requiring retry
     *
     * @param int $maxRetries Maximum retry attempts
     * @return array<array<string, mixed>>
     */
    public function getRetryable(int $maxRetries = 3): array;

    /**
     * Purge old notification history
     *
     * @param DateTimeImmutable $olderThan Delete records older than this date
     * @return int Number of records deleted
     */
    public function purge(DateTimeImmutable $olderThan): int;
}
