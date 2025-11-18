<?php

declare(strict_types=1);

namespace Nexus\Notifier\Contracts;

use Nexus\Notifier\ValueObjects\Category;
use Nexus\Notifier\ValueObjects\ChannelType;

/**
 * Notification Preference Repository Interface
 *
 * Manages recipient notification preferences and opt-out settings.
 */
interface NotificationPreferenceRepositoryInterface
{
    /**
     * Get notification preferences for a recipient
     *
     * @param string $recipientId
     * @return array{channels: array<string>, categories: array<string, bool>}
     */
    public function getPreferences(string $recipientId): array;

    /**
     * Update notification preferences
     *
     * @param string $recipientId
     * @param array<string, mixed> $preferences
     * @return bool True if updated successfully
     */
    public function updatePreferences(string $recipientId, array $preferences): bool;

    /**
     * Check if a notification can be sent to a recipient
     *
     * @param string $recipientId
     * @param Category $category
     * @param ChannelType $channel
     * @return bool True if allowed
     */
    public function canSendToRecipient(string $recipientId, Category $category, ChannelType $channel): bool;

    /**
     * Opt out recipient from a category
     *
     * @param string $recipientId
     * @param Category $category
     * @return bool True if opted out successfully
     */
    public function optOut(string $recipientId, Category $category): bool;

    /**
     * Opt in recipient to a category
     *
     * @param string $recipientId
     * @param Category $category
     * @return bool True if opted in successfully
     */
    public function optIn(string $recipientId, Category $category): bool;

    /**
     * Set preferred channels for a recipient
     *
     * @param string $recipientId
     * @param array<ChannelType> $channels
     * @return bool True if updated successfully
     */
    public function setPreferredChannels(string $recipientId, array $channels): bool;
}
