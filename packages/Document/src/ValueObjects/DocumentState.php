<?php

declare(strict_types=1);

namespace Nexus\Document\ValueObjects;

/**
 * Document state enumeration.
 *
 * Defines the lifecycle states of a document.
 */
enum DocumentState: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';

    /**
     * Get a human-readable label for the document state.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::ARCHIVED => 'Archived',
            self::DELETED => 'Deleted',
        };
    }

    /**
     * Check if the state allows editing.
     */
    public function isEditable(): bool
    {
        return match ($this) {
            self::DRAFT, self::PUBLISHED => true,
            self::ARCHIVED, self::DELETED => false,
        };
    }

    /**
     * Get the next allowed states for transition.
     *
     * @return array<DocumentState>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PUBLISHED, self::DELETED],
            self::PUBLISHED => [self::ARCHIVED, self::DELETED],
            self::ARCHIVED => [self::DELETED],
            self::DELETED => [],
        };
    }
}
