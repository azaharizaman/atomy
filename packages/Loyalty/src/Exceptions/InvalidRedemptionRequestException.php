<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Exceptions;

use RuntimeException;

/**
 * Thrown when a redemption request violates business constraints (e.g., incremental multiples, thresholds).
 * Requirements: FUN-LOY-302, FUN-LOY-303
 */
final class InvalidRedemptionRequestException extends RuntimeException
{
    public static function forConstraint(string $reason): self
    {
        return new self("Invalid redemption request: " . $reason);
    }
}
