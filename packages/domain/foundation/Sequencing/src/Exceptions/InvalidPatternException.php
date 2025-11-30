<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

use Exception;

/**
 * Exception thrown when an invalid pattern is provided.
 */
class InvalidPatternException extends Exception
{
    public static function invalidSyntax(string $pattern, string $reason): self
    {
        return new self("Invalid pattern syntax '{$pattern}': {$reason}");
    }

    public static function unknownVariable(string $variable): self
    {
        return new self("Unknown pattern variable: {$variable}");
    }

    public static function invalidPadding(string $variable, int $padding): self
    {
        return new self("Invalid padding size {$padding} for variable {$variable}");
    }
}
