<?php

declare(strict_types=1);

namespace Nexus\Sequencing\Exceptions;

use Exception;

/**
 * Exception thrown when a sequence counter is exhausted.
 */
class SequenceExhaustedException extends Exception
{
    public static function counterExhausted(string $sequenceName, int $currentValue, int $maxValue): self
    {
        return new self(
            "Sequence '{$sequenceName}' is exhausted. Current value: {$currentValue}, Maximum: {$maxValue}"
        );
    }

    public static function thresholdReached(string $sequenceName, int $currentValue, int $threshold): self
    {
        return new self(
            "Sequence '{$sequenceName}' has reached {$threshold}% threshold. Current value: {$currentValue}"
        );
    }
}
