<?php

declare(strict_types=1);

namespace Nexus\Task\Enums;

/**
 * Task lifecycle status. Used for completion tracking (FUN-PRO-0543).
 */
enum TaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return $this === self::Completed || $this === self::Cancelled;
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => \in_array($target, [self::InProgress, self::Completed, self::Cancelled], true),
            self::InProgress => \in_array($target, [self::Completed, self::Cancelled], true),
            self::Completed, self::Cancelled => false,
        };
    }
}
