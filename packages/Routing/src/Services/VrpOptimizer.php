<?php

declare(strict_types=1);

namespace Nexus\Routing\Services;

use Nexus\Routing\Contracts\RouteOptimizerInterface;
use Nexus\Routing\Contracts\ConstraintValidatorInterface;
use Nexus\Routing\ValueObjects\RouteStop;
use Nexus\Routing\ValueObjects\VehicleProfile;
use Nexus\Routing\ValueObjects\RouteConstraints;
use Nexus\Routing\ValueObjects\RouteOptimizationResult;
use Nexus\Routing\ValueObjects\OptimizedRoute;
use Nexus\Routing\ValueObjects\OptimizationMetrics;
use Nexus\Routing\Exceptions\NoFeasibleSolutionException;
use Nexus\Geo\Contracts\DistanceCalculatorInterface;
use Nexus\Geo\Contracts\TravelTimeInterface;
use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\ValueObjects\Distance;
use Psr\Log\LoggerInterface;

/**
 * VRP optimizer using greedy vehicle assignment
 * 
 * For advanced optimization, use OR-Tools integration (see ORToolsAdapter in Atomy)
 */
final readonly class VrpOptimizer implements RouteOptimizerInterface
{
    public function __construct(
        private DistanceCalculatorInterface $distanceCalculator,
        private TravelTimeInterface $travelTimeEstimator,
        private ConstraintValidatorInterface $constraintValidator,
        private LoggerInterface $logger
    ) {
    }

    public function optimizeTsp(
        array $stops,
        Coordinates $depotCoordinates,
        ?RouteConstraints $constraints = null
    ): RouteOptimizationResult {
        throw new \LogicException('VrpOptimizer does not support TSP. Use TspOptimizer instead.');
    }

    public function optimizeVrp(
        array $stops,
        array $vehicles,
        ?RouteConstraints $constraints = null
    ): RouteOptimizationResult {
        $startTime = microtime(true);

        if (empty($stops)) {
            throw NoFeasibleSolutionException::noStopsProvided();
        }

        if (empty($vehicles)) {
            throw NoFeasibleSolutionException::noVehiclesAvailable();
        }

        $constraints = $constraints ?? new RouteConstraints();

        // Validate total capacity
        $totalDemand = array_sum(array_map(fn($stop) => $stop->demand, $stops));
        $totalCapacity = array_sum(array_map(fn($vehicle) => $vehicle->capacity, $vehicles));

        if ($totalDemand > $totalCapacity) {
            throw NoFeasibleSolutionException::capacityExceeded($totalDemand, $totalCapacity);
        }

        // Assign stops to vehicles using greedy nearest-neighbor
        $vehicleRoutes = $this->assignStopsToVehicles($stops, $vehicles, $constraints);

        // Collect violations
        $allViolations = [];
        $totalDistance = new Distance(0);
        $totalDuration = 0;

        foreach ($vehicleRoutes as $route) {
            $violations = $this->constraintValidator->validate($route, $constraints);
            $allViolations = array_merge($allViolations, $violations);
            $totalDistance = $totalDistance->add($route->totalDistance);
            $totalDuration += $route->totalDurationSeconds;
        }

        $executionTime = (microtime(true) - $startTime) * 1000;

        $metrics = new OptimizationMetrics(
            executionTimeMs: $executionTime,
            algorithm: 'VRP (Greedy Assignment)',
            totalStops: count($stops),
            totalVehicles: count($vehicles),
            totalDistance: $totalDistance,
            totalDurationSeconds: $totalDuration,
            violations: $allViolations
        );

        $this->logger->info('VRP optimization complete', [
            'stops' => count($stops),
            'vehicles' => count($vehicles),
            'execution_time_ms' => round($executionTime, 2),
            'violations' => count($allViolations),
        ]);

        return new RouteOptimizationResult(
            routes: $vehicleRoutes,
            metrics: $metrics,
            violations: $allViolations
        );
    }

    public function getAlgorithmName(): string
    {
        return 'VRP (Greedy Assignment)';
    }

    public function supportsConstraint(string $constraintType): bool
    {
        return in_array($constraintType, ['max_capacity', 'max_duration', 'max_stops']);
    }

    /**
     * Assign stops to vehicles using greedy nearest-neighbor
     * 
     * @param array<RouteStop> $stops
     * @param array<VehicleProfile> $vehicles
     * @return array<string, OptimizedRoute>
     */
    private function assignStopsToVehicles(
        array $stops,
        array $vehicles,
        RouteConstraints $constraints
    ): array {
        $unassigned = $stops;
        $vehicleRoutes = [];

        foreach ($vehicles as $vehicle) {
            $route = [];
            $currentLoad = 0.0;
            $currentDuration = 0;
            $currentPosition = $vehicle->depotCoordinates;

            while (!empty($unassigned)) {
                $bestStopIndex = null;
                $bestDistance = PHP_FLOAT_MAX;

                // Find nearest feasible stop
                foreach ($unassigned as $index => $stop) {
                    // Check capacity constraint
                    if ($currentLoad + $stop->demand > $vehicle->capacity) {
                        continue;
                    }

                    $distance = $this->distanceCalculator->calculate($currentPosition, $stop->coordinates);
                    
                    if ($distance->meters < $bestDistance) {
                        $bestDistance = $distance->meters;
                        $bestStopIndex = $index;
                    }
                }

                // No more feasible stops for this vehicle
                if ($bestStopIndex === null) {
                    break;
                }

                $bestStop = $unassigned[$bestStopIndex];
                $route[] = $bestStop;
                $currentLoad += $bestStop->demand;
                $currentPosition = $bestStop->coordinates;

                unset($unassigned[$bestStopIndex]);
                $unassigned = array_values($unassigned);
            }

            // Build optimized route for this vehicle
            if (!empty($route)) {
                $vehicleRoutes[$vehicle->id] = $this->buildRoute($route, $vehicle, $vehicle->id);
            }
        }

        // Check for unassigned stops
        if (!empty($unassigned)) {
            $this->logger->warning('VRP: Some stops could not be assigned', [
                'unassigned_count' => count($unassigned),
            ]);
        }

        return $vehicleRoutes;
    }

    /**
     * Build OptimizedRoute from stop sequence
     * 
     * @param array<RouteStop> $stops
     */
    private function buildRoute(array $stops, VehicleProfile $vehicle, string $routeId): OptimizedRoute
    {
        $totalDistance = new Distance(0);
        $totalDuration = 0;
        $totalLoad = 0.0;

        $currentPosition = $vehicle->depotCoordinates;

        foreach ($stops as $stop) {
            $distance = $this->distanceCalculator->calculate($currentPosition, $stop->coordinates);
            $travelTime = $this->travelTimeEstimator->estimateTravelTime(
                $currentPosition,
                $stop->coordinates,
                'driving'
            );

            $totalDistance = $totalDistance->add($distance);
            $totalDuration += $travelTime + $stop->serviceDurationSeconds;
            $totalLoad += $stop->demand;
            $currentPosition = $stop->coordinates;
        }

        // Return to depot
        $returnDistance = $this->distanceCalculator->calculate($currentPosition, $vehicle->depotCoordinates);
        $returnTime = $this->travelTimeEstimator->estimateTravelTime($currentPosition, $vehicle->depotCoordinates, 'driving');
        
        $totalDistance = $totalDistance->add($returnDistance);
        $totalDuration += $returnTime;

        return new OptimizedRoute(
            routeId: $routeId,
            stops: $stops,
            totalDistance: $totalDistance,
            totalDurationSeconds: $totalDuration,
            totalLoad: $totalLoad
        );
    }
}
