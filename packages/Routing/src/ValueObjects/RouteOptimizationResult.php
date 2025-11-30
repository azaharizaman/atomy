<?php

declare(strict_types=1);

namespace Nexus\Routing\ValueObjects;

/**
 * Immutable value object representing route optimization result
 * 
 * Contains optimized routes, metrics, and constraint violations
 */
final readonly class RouteOptimizationResult implements \JsonSerializable
{
    /**
     * @param array<string, OptimizedRoute> $routes Map of vehicle ID => route
     * @param array<ConstraintViolation> $violations
     */
    public function __construct(
        public array $routes,
        public OptimizationMetrics $metrics,
        public array $violations = [],
        public ?OptimizedRoute $optimizedRoute = null // For TSP (single vehicle)
    ) {
    }

    /**
     * Check if result has any constraint violations
     */
    public function hasViolations(): bool
    {
        return !empty($this->violations);
    }

    /**
     * Get violations by type
     * 
     * @return array<ConstraintViolation>
     */
    public function getViolationsByType(string $type): array
    {
        return array_filter(
            $this->violations,
            fn(ConstraintViolation $v) => $v->type === $type
        );
    }

    /**
     * Get route for specific vehicle
     */
    public function getRouteForVehicle(string $vehicleId): ?OptimizedRoute
    {
        return $this->routes[$vehicleId] ?? null;
    }

    /**
     * Get total number of stops across all routes
     */
    public function getTotalStops(): int
    {
        return array_sum(array_map(
            fn(OptimizedRoute $route) => $route->getStopCount(),
            $this->routes
        ));
    }

    /**
     * Check if optimization is feasible
     */
    public function isFeasible(): bool
    {
        return !$this->hasViolations();
    }

    public function toArray(): array
    {
        return [
            'routes' => array_map(fn(OptimizedRoute $r) => $r->toArray(), $this->routes),
            'optimized_route' => $this->optimizedRoute?->toArray(),
            'metrics' => $this->metrics->toArray(),
            'violations' => array_map(fn(ConstraintViolation $v) => $v->toArray(), $this->violations),
            'is_feasible' => $this->isFeasible(),
            'total_stops' => $this->getTotalStops(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
