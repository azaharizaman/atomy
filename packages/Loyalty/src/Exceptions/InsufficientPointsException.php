<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Exceptions;

use RuntimeException;

/**
 * Thrown when a member attempts to redeem more points than they have available.
 * Requirement: REL-LOY-002
 */
final class InsufficientPointsException extends RuntimeException
{
    public static function forMember(string $memberId, int $requested, int $available): self
    {
        return new self(sprintf(
            "Member '%s' requested %d points, but only has %d available.",
            $memberId,
            $requested,
            $available
        ));
    }
}
