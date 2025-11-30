<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to generate from a locked sequence.
 */
class SequenceLockedException extends Exception
{
    public static function cannotGenerate(string $sequenceName): self
    {
        return new self("Cannot generate number from locked sequence '{$sequenceName}'");
    }

    public static function cannotModify(string $sequenceName): self
    {
        return new self("Cannot modify locked sequence '{$sequenceName}'");
    }
}
