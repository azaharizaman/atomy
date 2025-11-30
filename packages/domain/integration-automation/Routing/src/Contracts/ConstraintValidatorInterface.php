<?php

declare(strict_types=1);

namespace Nexus\Routing\Contracts;

use Nexus\Routing\ValueObjects\OptimizedRoute;
use Nexus\Routing\ValueObjects\RouteConstraints;
use Nexus\Routing\ValueObjects\ConstraintViolation;

/**
 * Framework-agnostic constraint validator interface
 * 
 * Validates routes against operational constraints
 */
interface ConstraintValidatorInterface
{
    /**
     * Validate route against constraints
     * 
     * @return array<ConstraintViolation>
     */
    public function validate(OptimizedRoute $route, RouteConstraints $constraints): array;

    /**
     * Validate time window constraints
     * 
     * @return array<ConstraintViolation>
     */
    public function validateTimeWindows(OptimizedRoute $route): array;

    /**
     * Validate capacity constraints
     * 
     * @return array<ConstraintViolation>
     */
    public function validateCapacity(OptimizedRoute $route, float $maxCapacity): array;

    /**
     * Validate duration constraints
     * 
     * @return array<ConstraintViolation>
     */
    public function validateDuration(OptimizedRoute $route, int $maxDurationSeconds): array;

    /**
     * Check if route is feasible (no critical violations)
     */
    public function isFeasible(OptimizedRoute $route, RouteConstraints $constraints): bool;

    /**
     * Calculate constraint violation severity (0.0 to 1.0)
     */
    public function calculateSeverity(ConstraintViolation $violation): float;
}
