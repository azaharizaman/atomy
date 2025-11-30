<?php

declare(strict_types=1);

namespace Nexus\Notifier\Contracts;

use DateTimeImmutable;

/**
 * Notification Queue Interface
 *
 * Manages the notification delivery queue.
 */
interface NotificationQueueInterface
{
    /**
     * Enqueue a notification for delivery
     *
     * @param array<string, mixed> $notificationData
     * @param DateTimeImmutable|null $scheduledAt When to process (null = immediate)
     * @return string Queue entry ID
     */
    public function enqueue(array $notificationData, ?DateTimeImmutable $scheduledAt = null): string;

    /**
     * Dequeue the next pending notification
     *
     * @return array<string, mixed>|null Notification data or null if queue is empty
     */
    public function dequeue(): ?array;

    /**
     * Get all pending notifications
     *
     * @param int $limit
     * @return array<array<string, mixed>>
     */
    public function getPending(int $limit = 100): array;

    /**
     * Retry a failed notification
     *
     * @param string $notificationId
     * @return bool True if re-queued successfully
     */
    public function retry(string $notificationId): bool;

    /**
     * Remove a notification from the queue
     *
     * @param string $notificationId
     * @return bool True if removed successfully
     */
    public function remove(string $notificationId): bool;

    /**
     * Get queue size
     *
     * @return int Number of pending notifications
     */
    public function size(): int;
}
