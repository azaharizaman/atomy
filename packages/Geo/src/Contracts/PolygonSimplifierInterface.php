<?php

declare(strict_types=1);

namespace Nexus\Geo\Contracts;

use Nexus\Geo\ValueObjects\PolygonSimplificationResult;
use Nexus\Geo\Exceptions\PolygonComplexityException;

/**
 * Framework-agnostic polygon simplification service interface
 * 
 * Uses Douglas-Peucker algorithm to reduce polygon complexity
 */
interface PolygonSimplifierInterface
{
    /**
     * Simplify polygon to reduce vertex count
     * 
     * @param array $polygon Array of [lat, lng] coordinate pairs
     * @param float $tolerance Tolerance in meters (higher = more aggressive simplification)
     * @param int $maxVertices Maximum allowed vertices (default 100)
     * @throws PolygonComplexityException
     */
    public function simplify(
        array $polygon,
        float $tolerance = 10.0,
        int $maxVertices = 100
    ): PolygonSimplificationResult;

    /**
     * Validate polygon complexity
     * 
     * @throws PolygonComplexityException
     */
    public function validateComplexity(array $polygon, int $maxVertices = 100): void;

    /**
     * Calculate recommended tolerance for target vertex count
     * 
     * @param array $polygon Array of [lat, lng] coordinate pairs
     * @param int $targetVertices Desired vertex count
     * @return float Recommended tolerance in meters
     */
    public function calculateRecommendedTolerance(array $polygon, int $targetVertices): float;

    /**
     * Check if polygon is already simple enough
     */
    public function isSimpleEnough(array $polygon, int $maxVertices = 100): bool;
}
