<?php

declare(strict_types=1);

namespace Nexus\Document\ValueObjects;

/**
 * Document relationship type enumeration.
 *
 * Defines the types of relationships between documents.
 */
enum RelationshipType: string
{
    case AMENDMENT = 'amendment';
    case SUPERSEDES = 'supersedes';
    case RELATED = 'related';
    case ATTACHMENT = 'attachment';

    /**
     * Get a human-readable label for the relationship type.
     */
    public function label(): string
    {
        return match ($this) {
            self::AMENDMENT => 'Amendment',
            self::SUPERSEDES => 'Supersedes',
            self::RELATED => 'Related',
            self::ATTACHMENT => 'Attachment',
        };
    }

    /**
     * Get a description of the relationship type.
     */
    public function description(): string
    {
        return match ($this) {
            self::AMENDMENT => 'This document amends the target document',
            self::SUPERSEDES => 'This document supersedes/replaces the target document',
            self::RELATED => 'This document is related to the target document',
            self::ATTACHMENT => 'This document is attached to the target document',
        };
    }
}
