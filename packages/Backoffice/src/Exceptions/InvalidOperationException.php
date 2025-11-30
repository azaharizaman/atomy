<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Exceptions;

use Exception;

class InvalidOperationException extends Exception
{
    public static function inactiveEntity(string $entityType, string $entityId): self
    {
        return new self("{$entityType} {$entityId} is inactive and cannot perform this operation");
    }

    public static function hasActiveChildren(string $entityType, string $entityId): self
    {
        return new self("Cannot delete {$entityType} {$entityId} because it has active children");
    }

    public static function hasActiveStaff(string $entityType, string $entityId): self
    {
        return new self("Cannot delete {$entityType} {$entityId} because it has active staff assignments");
    }
}
