<?php

declare(strict_types=1);

namespace Nexus\Sourcing\ValueObjects;

final readonly class RfqStatus
{
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';
    public const CLOSED = 'closed';
    public const AWARDED = 'awarded';
    public const CANCELLED = 'cancelled';

    public const ALL = [
        self::DRAFT,
        self::PUBLISHED,
        self::CLOSED,
        self::AWARDED,
        self::CANCELLED,
    ];
}
