<?php

declare(strict_types=1);

namespace Nexus\Notifier\Contracts;

/**
 * Notifiable Interface
 *
 * Entities that can receive notifications must implement this interface.
 * Provides abstraction for retrieving recipient contact information.
 */
interface NotifiableInterface
{
    /**
     * Get the recipient's email address for notifications
     */
    public function getNotificationEmail(): ?string;

    /**
     * Get the recipient's phone number for SMS notifications
     */
    public function getNotificationPhone(): ?string;

    /**
     * Get the recipient's device tokens for push notifications
     *
     * @return array<string>
     */
    public function getNotificationDeviceTokens(): array;

    /**
     * Get the recipient's preferred locale for notification content
     */
    public function getNotificationLocale(): string;

    /**
     * Get the recipient's timezone for scheduling
     */
    public function getNotificationTimezone(): string;

    /**
     * Get a unique identifier for this recipient
     */
    public function getNotificationIdentifier(): string;
}
