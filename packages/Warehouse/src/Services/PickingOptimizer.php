<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Services;

use Nexus\Warehouse\Contracts\PickingOptimizerInterface;
use Nexus\Warehouse\Contracts\PickRouteResult;
use Nexus\Warehouse\Contracts\BinLocationRepositoryInterface;
use Nexus\Warehouse\Contracts\BinLocationInterface;
use Nexus\Warehouse\Exceptions\BinLocationNotFoundException;
use Nexus\Routing\Contracts\RouteOptimizerInterface;
use Nexus\Routing\ValueObjects\RouteStop;
use Nexus\Geo\ValueObjects\Coordinates;
use Psr\Log\LoggerInterface;

/**
 * Picking optimizer service using TSP algorithm
 * 
 * Phase 1: Integrates with Nexus\Routing TspOptimizer
 */
final readonly class PickingOptimizer implements PickingOptimizerInterface
{
    public function __construct(
        private BinLocationRepositoryInterface $binRepository,
        private RouteOptimizerInterface $tspOptimizer,
        private LoggerInterface $logger
    ) {
    }
    
    public function optimizePickRoute(string $warehouseId, array $pickItems): PickRouteResult
    {
        $startTime = hrtime(true);
        
        $this->logger->info('Optimizing pick route', [
            'warehouse_id' => $warehouseId,
            'item_count' => count($pickItems),
        ]);
        
        // Build route stops from bin locations
        $stops = [];
        $binMap = [];
        
        foreach ($pickItems as $item) {
            $bin = $this->binRepository->findById($item['bin_id']);
            
            if ($bin === null) {
                throw BinLocationNotFoundException::withId($item['bin_id']);
            }
            
            $coordinates = $bin->getCoordinates();
            
            if ($coordinates === null) {
                // Bin has no GPS coordinates - fallback to sequential order
                $this->logger->warning('Bin missing coordinates, using sequential order', [
                    'bin_id' => $item['bin_id'],
                ]);
                continue;
            }
            
            $stops[] = new RouteStop(
                id: $item['bin_id'],
                coordinates: new Coordinates($coordinates['latitude'], $coordinates['longitude']),
                timeWindowStart: null,
                timeWindowEnd: null,
                serviceTimeSeconds: 60 // Default 1 minute per pick
            );
            
            $binMap[$item['bin_id']] = [
                'bin_code' => $bin->getCode(),
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ];
        }
        
        // If no coordinates, return sequential order
        if (empty($stops)) {
            return $this->createSequentialResult($pickItems, $startTime);
        }
        
        // Get warehouse depot coordinates (default to first bin if not set)
        $depotCoordinates = $stops[0]->coordinates;
        
        // Optimize using TSP
        $result = $this->tspOptimizer->optimizeTsp($stops, $depotCoordinates);
        
        // Build optimized sequence
        $optimizedSequence = [];
        foreach ($result->optimizedRoute->stops as $stop) {
            $optimizedSequence[] = array_merge(
                ['bin_id' => $stop->id],
                $binMap[$stop->id]
            );
        }
        
        // Calculate sequential distance for comparison
        $sequentialDistance = $this->calculateSequentialDistance($stops);
        $optimizedDistance = $result->optimizedRoute->totalDistance->toMeters();
        $improvement = $sequentialDistance > 0
            ? (($sequentialDistance - $optimizedDistance) / $sequentialDistance) * 100
            : 0.0;
        
        $executionTime = (int) ((hrtime(true) - $startTime) / 1_000_000); // Convert to ms
        
        $this->logger->info('Pick route optimized', [
            'item_count' => count($optimizedSequence),
            'distance_meters' => $optimizedDistance,
            'improvement_pct' => round($improvement, 2),
            'execution_ms' => $executionTime,
        ]);
        
        return new PickRouteResultValue(
            $optimizedSequence,
            $optimizedDistance,
            $improvement,
            $executionTime
        );
    }
    
    private function calculateSequentialDistance(array $stops): float
    {
        // Simple sequential distance calculation
        $distance = 0.0;
        for ($i = 0; $i < count($stops) - 1; $i++) {
            $distance += $stops[$i]->coordinates->distanceTo($stops[$i + 1]->coordinates)->toMeters();
        }
        return $distance;
    }
    
    private function createSequentialResult(array $pickItems, int $startTime): PickRouteResult
    {
        return new PickRouteResultValue(
            $pickItems,
            0.0,
            0.0,
            (int) ((hrtime(true) - $startTime) / 1_000_000)
        );
    }
}

/**
 * Pick route result value object
 */
final readonly class PickRouteResultValue implements PickRouteResult
{
    public function __construct(
        private array $optimizedSequence,
        private float $totalDistance,
        private float $distanceImprovement,
        private int $executionTime
    ) {
    }
    
    public function getOptimizedSequence(): array
    {
        return $this->optimizedSequence;
    }
    
    public function getTotalDistance(): float
    {
        return $this->totalDistance;
    }
    
    public function getDistanceImprovement(): float
    {
        return $this->distanceImprovement;
    }
    
    public function getExecutionTime(): int
    {
        return $this->executionTime;
    }
}
