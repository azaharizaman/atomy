<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Exceptions;

/**
 * Exception for invalid threshold configuration.
 */
final class InvalidThresholdException extends VarianceCalculationException
{
    public function __construct(
        string $thresholdName,
        string $reason,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            "Invalid threshold '{$thresholdName}': {$reason}",
            $code,
            $previous
        );
    }
}
