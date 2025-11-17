<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Exceptions;

use Exception;

class CircularReferenceException extends Exception
{
    public function __construct(string $entityType, string $entityId, string $proposedParentId)
    {
        parent::__construct(
            "Circular reference detected: {$entityType} {$entityId} cannot have {$proposedParentId} as parent"
        );
    }
}
