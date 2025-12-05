<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Enums;

/**
 * Journal Entry Status.
 *
 * Represents the lifecycle states of a journal entry.
 *
 * State Transitions:
 * - Draft → Posted (via post action)
 * - Posted → Reversed (via reverse action)
 * - Draft → (deleted) (only drafts can be deleted)
 */
enum JournalEntryStatus: string
{
    /**
     * Entry created but not yet finalized.
     * Can be edited or deleted.
     */
    case DRAFT = 'draft';

    /**
     * Entry posted to the general ledger.
     * Immutable - cannot be edited, only reversed.
     */
    case POSTED = 'posted';

    /**
     * Entry has been reversed with offsetting entry.
     */
    case REVERSED = 'reversed';

    /**
     * Check if this status allows editing.
     */
    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if this status allows posting.
     */
    public function canBePosted(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if this status allows reversal.
     */
    public function canBeReversed(): bool
    {
        return $this === self::POSTED;
    }

    /**
     * Check if this status allows deletion.
     */
    public function canBeDeleted(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if the entry is finalized (posted or reversed).
     */
    public function isFinalized(): bool
    {
        return $this !== self::DRAFT;
    }

    /**
     * Get display label.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::POSTED => 'Posted',
            self::REVERSED => 'Reversed',
        };
    }
}
