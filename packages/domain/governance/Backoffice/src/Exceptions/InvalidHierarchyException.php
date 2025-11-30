<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Exceptions;

use Exception;

class InvalidHierarchyException extends Exception
{
    public static function maxDepthExceeded(string $entityType, int $maxDepth, int $currentDepth): self
    {
        return new self(
            "{$entityType} hierarchy depth ({$currentDepth}) exceeds maximum allowed depth ({$maxDepth})"
        );
    }

    public static function crossBoundary(string $childType, string $parentType): self
    {
        return new self("{$childType} hierarchy cannot exceed {$parentType} boundaries");
    }
}
