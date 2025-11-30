<?php

declare(strict_types=1);

namespace Nexus\Notifier\Contracts;

use Nexus\Notifier\ValueObjects\ChannelType;
use Nexus\Notifier\ValueObjects\DeliveryStatus;

/**
 * Notification Channel Interface
 *
 * Defines contract for pluggable notification delivery channels.
 * Implementations handle actual sending via external providers.
 */
interface NotificationChannelInterface
{
    /**
     * Send a notification via this channel
     *
     * @param NotifiableInterface $recipient The notification recipient
     * @param NotificationInterface $notification The notification to send
     * @param array<string, mixed> $content Channel-specific content
     * @return string Delivery tracking ID
     * @throws \Nexus\Notifier\Exceptions\DeliveryFailedException
     */
    public function send(
        NotifiableInterface $recipient,
        NotificationInterface $notification,
        array $content
    ): string;

    /**
     * Check if this channel supports the given notification
     */
    public function supports(NotificationInterface $notification): bool;

    /**
     * Get the channel type this handler implements
     */
    public function getChannelType(): ChannelType;

    /**
     * Get delivery status for a tracking ID
     */
    public function getDeliveryStatus(string $trackingId): DeliveryStatus;

    /**
     * Check if the channel is currently available
     */
    public function isAvailable(): bool;
}
