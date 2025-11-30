<?php

declare(strict_types=1);

namespace Nexus\Content\Exceptions;

/**
 * Thrown when content version is not found
 */
class ContentVersionNotFoundException extends ContentException
{
    public static function forId(string $versionId): self
    {
        return new self("Content version not found: {$versionId}");
    }
}
