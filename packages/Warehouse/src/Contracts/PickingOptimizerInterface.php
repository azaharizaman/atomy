<?php

declare(strict_types=1);

namespace Nexus\Warehouse\Contracts;

/**
 * Picking optimization interface
 * 
 * Phase 1: TSP-based route optimization
 */
interface PickingOptimizerInterface
{
    /**
     * Optimize pick route using TSP algorithm
     * 
     * @param string $warehouseId Warehouse identifier
     * @param array<array{bin_id: string, product_id: string, quantity: float}> $pickItems Items to pick
     * @return PickRouteResult Optimized route with metrics
     */
    public function optimizePickRoute(string $warehouseId, array $pickItems): PickRouteResult;
}

/**
 * Pick route optimization result
 */
interface PickRouteResult
{
    /**
     * Get optimized sequence of bin locations
     * 
     * @return array<array{bin_id: string, bin_code: string, product_id: string, quantity: float}>
     */
    public function getOptimizedSequence(): array;
    
    /**
     * Get total distance in meters
     */
    public function getTotalDistance(): float;
    
    /**
     * Get distance improvement percentage vs. sequential picking
     */
    public function getDistanceImprovement(): float;
    
    /**
     * Get execution time in milliseconds
     */
    public function getExecutionTime(): int;
}
