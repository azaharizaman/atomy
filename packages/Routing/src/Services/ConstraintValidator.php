<?php

declare(strict_types=1);

namespace Nexus\Routing\Services;

use Nexus\Routing\Contracts\ConstraintValidatorInterface;
use Nexus\Routing\ValueObjects\OptimizedRoute;
use Nexus\Routing\ValueObjects\RouteConstraints;
use Nexus\Routing\ValueObjects\ConstraintViolation;

/**
 * Stateless constraint validator
 * 
 * Validates routes against operational constraints
 */
final readonly class ConstraintValidator implements ConstraintValidatorInterface
{
    public function validate(OptimizedRoute $route, RouteConstraints $constraints): array
    {
        $violations = [];

        // Validate time windows
        if ($constraints->enforceTimeWindows) {
            $violations = array_merge($violations, $this->validateTimeWindows($route));
        }

        // Validate capacity
        if ($constraints->maxCapacity !== null) {
            $violations = array_merge($violations, $this->validateCapacity($route, $constraints->maxCapacity));
        }

        // Validate duration
        if ($constraints->maxDurationSeconds !== null) {
            $violations = array_merge($violations, $this->validateDuration($route, $constraints->maxDurationSeconds));
        }

        // Validate stop count
        if ($constraints->maxStops !== null && $route->getStopCount() > $constraints->maxStops) {
            $violations[] = new ConstraintViolation(
                type: 'max_stops_exceeded',
                description: "Route has {$route->getStopCount()} stops, exceeding maximum of {$constraints->maxStops}",
                vehicleId: $route->routeId,
                severity: 0.5
            );
        }

        // Validate distance
        if ($constraints->maxDistanceKm !== null && $route->totalDistance->toKilometers() > $constraints->maxDistanceKm) {
            $violations[] = new ConstraintViolation(
                type: 'max_distance_exceeded',
                description: sprintf(
                    "Route distance %.2f km exceeds maximum of %.2f km",
                    $route->totalDistance->toKilometers(),
                    $constraints->maxDistanceKm
                ),
                vehicleId: $route->routeId,
                severity: 0.6
            );
        }

        return $violations;
    }

    public function validateTimeWindows(OptimizedRoute $route): array
    {
        $violations = [];
        $currentTime = new \DateTimeImmutable(); // Start time (should be passed as parameter in production)
        $elapsedSeconds = 0;

        foreach ($route->stops as $index => $stop) {
            if (!$stop->hasTimeWindow()) {
                continue;
            }

            $arrivalTime = $currentTime->modify("+{$elapsedSeconds} seconds");

            if (!$stop->isArrivalValid($arrivalTime)) {
                $violations[] = new ConstraintViolation(
                    type: 'time_window_violation',
                    description: sprintf(
                        "Stop '%s' arrival at %s outside time window %s - %s",
                        $stop->id,
                        $arrivalTime->format('H:i'),
                        $stop->timeWindowStart->format('H:i'),
                        $stop->timeWindowEnd->format('H:i')
                    ),
                    vehicleId: $route->routeId,
                    stopId: $stop->id,
                    severity: 0.9 // Time windows are critical
                );
            }

            // Update elapsed time
            $elapsedSeconds += $stop->serviceDurationSeconds;
        }

        return $violations;
    }

    public function validateCapacity(OptimizedRoute $route, float $maxCapacity): array
    {
        $violations = [];

        if ($route->totalLoad > $maxCapacity) {
            $violations[] = new ConstraintViolation(
                type: 'capacity_exceeded',
                description: sprintf(
                    "Total load %.2f exceeds vehicle capacity %.2f",
                    $route->totalLoad,
                    $maxCapacity
                ),
                vehicleId: $route->routeId,
                severity: 1.0 // Critical violation
            );
        }

        return $violations;
    }

    public function validateDuration(OptimizedRoute $route, int $maxDurationSeconds): array
    {
        $violations = [];

        if ($route->totalDurationSeconds > $maxDurationSeconds) {
            $violations[] = new ConstraintViolation(
                type: 'duration_exceeded',
                description: sprintf(
                    "Total duration %s exceeds maximum %s",
                    gmdate('H:i:s', $route->totalDurationSeconds),
                    gmdate('H:i:s', $maxDurationSeconds)
                ),
                vehicleId: $route->routeId,
                severity: 0.7
            );
        }

        return $violations;
    }

    public function isFeasible(OptimizedRoute $route, RouteConstraints $constraints): bool
    {
        $violations = $this->validate($route, $constraints);
        
        // Route is feasible if there are no critical violations
        foreach ($violations as $violation) {
            if ($violation->isCritical()) {
                return false;
            }
        }

        return true;
    }

    public function calculateSeverity(ConstraintViolation $violation): float
    {
        return $violation->severity ?? 0.5;
    }
}
