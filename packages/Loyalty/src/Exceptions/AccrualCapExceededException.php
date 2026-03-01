<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Exceptions;

use RuntimeException;

/**
 * Thrown when an accrual request exceeds safety caps (per-transaction or per-window).
 * Requirement: FUN-LOY-103
 */
final class AccrualCapExceededException extends RuntimeException
{
    public static function forTransaction(int $requested, int $cap): self
    {
        return new self(sprintf(
            "Accrual request of %d points exceeds transaction cap of %d.",
            $requested,
            $cap
        ));
    }

    public static function forWindow(int $requested, int $remainingWindowCap): self
    {
        return new self(sprintf(
            "Accrual request of %d points exceeds remaining 24h window cap of %d.",
            $requested,
            $remainingWindowCap
        ));
    }
}
