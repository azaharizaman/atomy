<?php

declare(strict_types=1);

namespace Nexus\Routing\Contracts;

use Nexus\Routing\ValueObjects\RouteStop;
use Nexus\Routing\ValueObjects\VehicleProfile;
use Nexus\Routing\ValueObjects\RouteConstraints;
use Nexus\Routing\ValueObjects\RouteOptimizationResult;
use Nexus\Geo\ValueObjects\Coordinates;

/**
 * Framework-agnostic route optimizer interface
 * 
 * Implementations provide TSP and VRP solving algorithms
 */
interface RouteOptimizerInterface
{
    /**
     * Optimize route for single vehicle (TSP)
     * 
     * @param array<RouteStop> $stops
     */
    public function optimizeTsp(
        array $stops,
        Coordinates $depotCoordinates,
        ?RouteConstraints $constraints = null
    ): RouteOptimizationResult;

    /**
     * Optimize routes for multiple vehicles (VRP)
     * 
     * @param array<RouteStop> $stops
     * @param array<VehicleProfile> $vehicles
     */
    public function optimizeVrp(
        array $stops,
        array $vehicles,
        ?RouteConstraints $constraints = null
    ): RouteOptimizationResult;

    /**
     * Get algorithm name
     */
    public function getAlgorithmName(): string;

    /**
     * Check if optimizer supports constraint type
     */
    public function supportsConstraint(string $constraintType): bool;
}
