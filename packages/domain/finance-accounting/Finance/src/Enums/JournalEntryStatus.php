<?php

declare(strict_types=1);

namespace Nexus\Finance\Enums;

/**
 * Journal Entry Status Enum
 * 
 * Defines the lifecycle states of a journal entry.
 */
enum JournalEntryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';

    public function label(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::Posted => 'Posted',
            self::Reversed => 'Reversed',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::Draft => in_array($newStatus, [self::Posted], true),
            self::Posted => in_array($newStatus, [self::Reversed], true),
            self::Reversed => false, // Cannot transition from reversed
        };
    }

    public function isPosted(): bool
    {
        return $this === self::Posted;
    }

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isReversed(): bool
    {
        return $this === self::Reversed;
    }

    public function canBeEdited(): bool
    {
        return $this === self::Draft;
    }

    public function canBeDeleted(): bool
    {
        return $this === self::Draft;
    }
}
