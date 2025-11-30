<?php

declare(strict_types=1);

namespace Nexus\Notifier\ValueObjects;

/**
 * Notification Category
 *
 * Used for preference management and opt-out controls.
 * Transactional notifications cannot be opted out of.
 */
enum Category: string
{
    case Transactional = 'transactional';
    case Marketing = 'marketing';
    case System = 'system';
    case Alert = 'alert';

    /**
     * Check if this category can be opted out of
     */
    public function isOptOutable(): bool
    {
        return match ($this) {
            self::Transactional => false,
            self::System => false,
            self::Marketing => true,
            self::Alert => true,
        };
    }

    /**
     * Get human-readable label
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Transactional => 'Transactional',
            self::Marketing => 'Marketing & Promotions',
            self::System => 'System Notifications',
            self::Alert => 'Alerts & Reminders',
        };
    }
}
