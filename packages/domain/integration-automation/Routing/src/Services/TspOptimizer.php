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
 * TSP optimizer using Nearest-Neighbor heuristic with 2-Opt refinement
 * 
 * Stateless service for single-vehicle route optimization
 */
final readonly class TspOptimizer implements RouteOptimizerInterface
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
        $startTime = microtime(true);

        if (empty($stops)) {
            throw NoFeasibleSolutionException::noStopsProvided();
        }

        $constraints = $constraints ?? new RouteConstraints();

        // Step 1: Nearest-Neighbor construction
        $initialSequence = $this->nearestNeighbor($stops, $depotCoordinates);
        $initialRoute = $this->buildRoute($initialSequence, $depotCoordinates, 'tsp_initial');

        // Step 2: 2-Opt refinement
        $optimizedSequence = $this->twoOpt($initialSequence, $depotCoordinates);
        $optimizedRoute = $this->buildRoute($optimizedSequence, $depotCoordinates, 'tsp_2opt');

        // Step 3: Validate constraints
        $violations = $this->constraintValidator->validate($optimizedRoute, $constraints);

        $executionTime = (microtime(true) - $startTime) * 1000;

        $metrics = new OptimizationMetrics(
            executionTimeMs: $executionTime,
            algorithm: 'TSP (Nearest-Neighbor + 2-Opt)',
            totalStops: count($stops),
            totalVehicles: 1,
            totalDistance: $optimizedRoute->totalDistance,
            totalDurationSeconds: $optimizedRoute->totalDurationSeconds,
            initialDistance: $initialRoute->totalDistance,
            initialDurationSeconds: $initialRoute->totalDurationSeconds,
            violations: $violations
        );

        $this->logger->info('TSP optimization complete', [
            'stops' => count($stops),
            'execution_time_ms' => round($executionTime, 2),
            'distance_improvement_pct' => round($metrics->getDistanceImprovement() ?? 0, 2),
            'violations' => count($violations),
        ]);

        return new RouteOptimizationResult(
            routes: [],
            metrics: $metrics,
            violations: $violations,
            optimizedRoute: $optimizedRoute
        );
    }

    public function optimizeVrp(
        array $stops,
        array $vehicles,
        ?RouteConstraints $constraints = null
    ): RouteOptimizationResult {
        throw new \LogicException('TspOptimizer does not support VRP. Use VrpOptimizer instead.');
    }

    public function getAlgorithmName(): string
    {
        return 'TSP (Nearest-Neighbor + 2-Opt)';
    }

    public function supportsConstraint(string $constraintType): bool
    {
        return in_array($constraintType, ['max_duration', 'max_distance', 'return_to_depot']);
    }

    /**
     * Nearest-Neighbor heuristic
     * 
     * @param array<RouteStop> $stops
     * @return array<RouteStop>
     */
    private function nearestNeighbor(array $stops, Coordinates $depot): array
    {
        $unvisited = $stops;
        $route = [];
        $currentPosition = $depot;

        while (!empty($unvisited)) {
            $nearestIndex = null;
            $nearestDistance = PHP_FLOAT_MAX;

            foreach ($unvisited as $index => $stop) {
                $distance = $this->distanceCalculator->calculate($currentPosition, $stop->coordinates);
                
                if ($distance->meters < $nearestDistance) {
                    $nearestDistance = $distance->meters;
                    $nearestIndex = $index;
                }
            }

            $nearestStop = $unvisited[$nearestIndex];
            $route[] = $nearestStop;
            $currentPosition = $nearestStop->coordinates;
            unset($unvisited[$nearestIndex]);
            $unvisited = array_values($unvisited); // Reindex
        }

        return $route;
    }

    /**
     * 2-Opt local search improvement
     * 
     * @param array<RouteStop> $route
     * @return array<RouteStop>
     */
    private function twoOpt(array $route, Coordinates $depot): array
    {
        $n = count($route);
        $improved = true;
        $bestRoute = $route;

        while ($improved) {
            $improved = false;

            for ($i = 0; $i < $n - 1; $i++) {
                for ($j = $i + 2; $j < $n; $j++) {
                    $newRoute = $this->twoOptSwap($bestRoute, $i, $j);
                    
                    if ($this->calculateTotalDistance($newRoute, $depot)->meters < 
                        $this->calculateTotalDistance($bestRoute, $depot)->meters) {
                        $bestRoute = $newRoute;
                        $improved = true;
                    }
                }
            }
        }

        return $bestRoute;
    }

    /**
     * Perform 2-opt swap
     * 
     * @param array<RouteStop> $route
     * @return array<RouteStop>
     */
    private function twoOptSwap(array $route, int $i, int $j): array
    {
        $newRoute = array_merge(
            array_slice($route, 0, $i + 1),
            array_reverse(array_slice($route, $i + 1, $j - $i)),
            array_slice($route, $j + 1)
        );

        return $newRoute;
    }

    /**
     * Build OptimizedRoute from stop sequence
     * 
     * @param array<RouteStop> $stops
     */
    private function buildRoute(array $stops, Coordinates $depot, string $routeId): OptimizedRoute
    {
        $totalDistance = $this->calculateTotalDistance($stops, $depot);
        $totalDuration = 0;
        $totalLoad = 0.0;

        $currentPosition = $depot;

        foreach ($stops as $stop) {
            $distance = $this->distanceCalculator->calculate($currentPosition, $stop->coordinates);
            $travelTime = $this->travelTimeEstimator->estimateTravelTime(
                $currentPosition,
                $stop->coordinates,
                'driving'
            );

            $totalDuration += $travelTime + $stop->serviceDurationSeconds;
            $totalLoad += $stop->demand;
            $currentPosition = $stop->coordinates;
        }

        // Return to depot
        $returnDistance = $this->distanceCalculator->calculate($currentPosition, $depot);
        $returnTime = $this->travelTimeEstimator->estimateTravelTime($currentPosition, $depot, 'driving');
        $totalDuration += $returnTime;

        return new OptimizedRoute(
            routeId: $routeId,
            stops: $stops,
            totalDistance: $totalDistance,
            totalDurationSeconds: $totalDuration,
            totalLoad: $totalLoad
        );
    }

    /**
     * Calculate total distance for route
     * 
     * @param array<RouteStop> $stops
     */
    private function calculateTotalDistance(array $stops, Coordinates $depot): Distance
    {
        $totalMeters = 0;
        $currentPosition = $depot;

        foreach ($stops as $stop) {
            $distance = $this->distanceCalculator->calculate($currentPosition, $stop->coordinates);
            $totalMeters += $distance->meters;
            $currentPosition = $stop->coordinates;
        }

        // Return to depot
        $returnDistance = $this->distanceCalculator->calculate($currentPosition, $depot);
        $totalMeters += $returnDistance->meters;

        return new Distance($totalMeters);
    }
}
