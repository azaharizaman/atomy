<?php

declare(strict_types=1);

namespace Nexus\AccountVarianceAnalysis\Exceptions;

/**
 * Exception when there is insufficient data for analysis.
 */
final class InsufficientDataException extends VarianceCalculationException
{
    public function __construct(
        int $required,
        int $provided,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            "Insufficient data: required {$required} periods, got {$provided}",
            $code,
            $previous
        );
    }
}
