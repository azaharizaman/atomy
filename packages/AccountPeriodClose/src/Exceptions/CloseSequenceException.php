<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Exceptions;

/**
 * Exception for close sequence errors.
 */
final class CloseSequenceException extends PeriodCloseException
{
    public function __construct(
        string $currentStep,
        string $expectedStep,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            "Invalid close sequence: expected '{$expectedStep}', but currently at '{$currentStep}'",
            $code,
            $previous
        );
    }
}
