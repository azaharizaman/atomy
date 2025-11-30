<?php

declare(strict_types=1);

namespace Nexus\Accounting\Core\Enums;

/**
 * Period close status.
 */
enum PeriodCloseStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case CLOSED = 'closed';
    case REOPENED = 'reopened';

    /**
     * Get the display name.
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::OPEN => 'Open',
            self::IN_PROGRESS => 'In Progress',
            self::CLOSED => 'Closed',
            self::REOPENED => 'Reopened',
        };
    }

    /**
     * Check if posting is allowed.
     */
    public function allowsPosting(): bool
    {
        return match($this) {
            self::OPEN, self::IN_PROGRESS, self::REOPENED => true,
            self::CLOSED => false,
        };
    }

    /**
     * Check if period can be closed.
     */
    public function canBeClosed(): bool
    {
        return match($this) {
            self::OPEN, self::REOPENED => true,
            self::IN_PROGRESS, self::CLOSED => false,
        };
    }

    /**
     * Check if period can be reopened.
     */
    public function canBeReopened(): bool
    {
        return $this === self::CLOSED;
    }
}
