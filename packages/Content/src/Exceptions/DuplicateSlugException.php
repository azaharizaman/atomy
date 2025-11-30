<?php

declare(strict_types=1);

namespace Nexus\Content\Exceptions;

/**
 * Thrown when slug is already in use
 */
class DuplicateSlugException extends ContentException
{
    public static function forSlug(string $slug): self
    {
        return new self("Article slug already exists: {$slug}");
    }
}
