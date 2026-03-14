<?php

declare(strict_types=1);

namespace Nexus\ResourceAllocation\Exceptions;

/**
 * Thrown when allocation would exceed 100% per user per day (BUS-PRO-0084, REL-PRO-0402).
 */
final class OverallocationException extends ResourceAllocationException
{
    public static function dailyCapExceeded(string $userId, float $total, float $max = 100.0): self
    {
        return new self(sprintf(
            'Resource allocation for user %s would exceed %s%% per day (total: %s%%).',
            $userId,
            $max,
            $total
        ));
    }
}
