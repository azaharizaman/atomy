<?php

declare(strict_types=1);

namespace Nexus\Notifier\ValueObjects;

/**
 * Notification Priority Level
 *
 * Determines queue ordering and delivery urgency.
 * Critical notifications bypass rate limiting and normal queues.
 */
enum Priority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Critical = 'critical';

    /**
     * Check if this priority bypasses rate limiting
     */
    public function bypassesRateLimit(): bool
    {
        return $this === self::Critical;
    }

    /**
     * Get queue priority weight for ordering
     */
    public function getWeight(): int
    {
        return match ($this) {
            self::Low => 10,
            self::Normal => 20,
            self::High => 30,
            self::Critical => 40,
        };
    }
}
