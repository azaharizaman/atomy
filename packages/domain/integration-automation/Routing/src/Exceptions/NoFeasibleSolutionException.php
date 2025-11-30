<?php

declare(strict_types=1);

namespace Nexus\Routing\Exceptions;

/**
 * Exception thrown when no feasible route solution exists
 */
class NoFeasibleSolutionException extends RouteOptimizationException
{
    public static function noVehiclesAvailable(): self
    {
        return new self('No vehicles available for route optimization');
    }

    public static function capacityExceeded(float $totalDemand, float $totalCapacity): self
    {
        return new self(
            "Total demand ({$totalDemand}) exceeds total vehicle capacity ({$totalCapacity})"
        );
    }

    public static function timeWindowsImpossible(string $reason): self
    {
        return new self("Time windows cannot be satisfied: {$reason}");
    }

    public static function noStopsProvided(): self
    {
        return new self('No stops provided for optimization');
    }

    public static function orToolsFailed(string $error): self
    {
        return new self("OR-Tools optimization failed: {$error}");
    }
}
