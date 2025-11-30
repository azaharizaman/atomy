<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

use Exception;

/**
 * Exception thrown when pattern collision is detected.
 */
class PatternCollisionException extends Exception
{
    public static function collision(string $pattern1, string $pattern2): self
    {
        return new self(
            "Pattern collision detected between '{$pattern1}' and '{$pattern2}'. " .
            "These patterns could generate identical numbers."
        );
    }
}
