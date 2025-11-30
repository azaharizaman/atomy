<?php

declare(strict_types=1);

namespace Nexus\Compliance\Exceptions;

/**
 * Exception thrown when a SOD rule is not found.
 */
class RuleNotFoundException extends \RuntimeException
{
    public function __construct(string $identifier)
    {
        parent::__construct(
            "SOD rule not found: '{$identifier}'."
        );
    }
}
