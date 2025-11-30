<?php

declare(strict_types=1);

namespace Nexus\Notifier\Contracts;

use DateTimeImmutable;

/**
 * Notification Manager Interface
 *
 * Primary API for triggering notifications.
 * This is the main entry point for business logic packages.
 */
interface NotificationManagerInterface
{
    /**
     * Send a notification to a single recipient
     *
     * @param NotifiableInterface $recipient The notification recipient
     * @param NotificationInterface $notification The notification to send
     * @param array<string>|null $channels Specific channels to use (null = all preferred)
     * @return string Notification tracking ID
     * @throws \Nexus\Notifier\Exceptions\InvalidRecipientException
     * @throws \Nexus\Notifier\Exceptions\InvalidChannelException
     */
    public function send(
        NotifiableInterface $recipient,
        NotificationInterface $notification,
        ?array $channels = null
    ): string;

    /**
     * Send a notification to multiple recipients
     *
     * @param array<NotifiableInterface> $recipients
     * @param NotificationInterface $notification
     * @param array<string>|null $channels
     * @return array<string> Tracking IDs indexed by recipient identifier
     */
    public function sendBatch(
        array $recipients,
        NotificationInterface $notification,
        ?array $channels = null
    ): array;

    /**
     * Schedule a notification for future delivery
     *
     * @param NotifiableInterface $recipient
     * @param NotificationInterface $notification
     * @param DateTimeImmutable $scheduledAt When to send the notification
     * @param array<string>|null $channels
     * @return string Scheduled notification ID
     */
    public function schedule(
        NotifiableInterface $recipient,
        NotificationInterface $notification,
        DateTimeImmutable $scheduledAt,
        ?array $channels = null
    ): string;

    /**
     * Cancel a scheduled notification
     *
     * @param string $notificationId
     * @return bool True if cancelled successfully
     */
    public function cancel(string $notificationId): bool;

    /**
     * Get notification delivery status
     *
     * @param string $notificationId
     * @return array{status: string, channels: array<string, string>}
     */
    public function getStatus(string $notificationId): array;
}
