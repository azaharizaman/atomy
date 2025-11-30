<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

use Exception;

/**
 * Exception thrown when an invalid overflow behavior is provided.
 */
class InvalidOverflowBehaviorException extends Exception
{
    public static function unknownBehavior(string $behavior): self
    {
        return new self(
            "Invalid overflow behavior: '{$behavior}'. Must be one of: throw_exception, switch_pattern, extend_padding"
        );
    }
}
