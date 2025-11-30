<?php

declare(strict_types=1);

namespace Nexus\Compliance\Exceptions;

/**
 * Exception thrown when a compliance scheme is not found.
 */
class SchemeNotFoundException extends \RuntimeException
{
    public function __construct(string $identifier)
    {
        parent::__construct(
            "Compliance scheme not found: '{$identifier}'."
        );
    }
}
