<?php

declare(strict_types=1);

namespace Nexus\Compliance\Exceptions;

/**
 * Exception thrown when attempting to activate an already active compliance scheme.
 */
class SchemeAlreadyActiveException extends \RuntimeException
{
    public function __construct(string $schemeName, string $tenantId)
    {
        parent::__construct(
            "Compliance scheme '{$schemeName}' is already active for tenant '{$tenantId}'."
        );
    }
}
