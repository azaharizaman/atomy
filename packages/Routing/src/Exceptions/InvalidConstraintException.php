<?php

declare(strict_types=1);

namespace Nexus\Routing\Exceptions;

/**
 * Exception thrown when route constraints are invalid
 */
class InvalidConstraintException extends RouteOptimizationException
{
    public static function invalidTimeWindow(string $stopId, string $reason): self
    {
        return new self("Invalid time window for stop '{$stopId}': {$reason}");
    }

    public static function invalidCapacity(float $capacity): self
    {
        return new self("Invalid vehicle capacity: {$capacity}. Must be positive.");
    }

    public static function invalidDuration(int $duration): self
    {
        return new self("Invalid max duration: {$duration}. Must be positive.");
    }

    public static function exceedsLimit(string $constraint, float $value, float $limit): self
    {
        return new self(
            "Constraint '{$constraint}' exceeded: {$value} > {$limit}"
        );
    }
}
