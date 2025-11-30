<?php

declare(strict_types=1);

namespace Nexus\Compliance\Exceptions;

/**
 * Exception thrown when an invalid compliance scheme is referenced.
 */
class InvalidSchemeException extends \RuntimeException
{
    public function __construct(string $schemeName)
    {
        parent::__construct(
            "Invalid compliance scheme: '{$schemeName}'."
        );
    }
}
