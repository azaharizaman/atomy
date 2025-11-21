<?php

declare(strict_types=1);

namespace Nexus\Geo\Contracts;

use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\ValueObjects\Distance;

/**
 * Framework-agnostic distance calculation service interface
 * 
 * Uses Haversine formula for great-circle distance
 */
interface DistanceCalculatorInterface
{
    /**
     * Calculate distance between two coordinates
     */
    public function calculate(Coordinates $from, Coordinates $to): Distance;

    /**
     * Calculate distances from one point to multiple destinations
     * 
     * @param array<Coordinates> $destinations
     * @return array<Distance> Array of distances in same order as destinations
     */
    public function calculateBatch(Coordinates $from, array $destinations): array;

    /**
     * Find nearest coordinate from a list
     * 
     * @param array<Coordinates> $candidates
     * @return array{index: int, coordinates: Coordinates, distance: Distance}
     */
    public function findNearest(Coordinates $from, array $candidates): array;

    /**
     * Sort coordinates by distance from origin
     * 
     * @param array<Coordinates> $coordinates
     * @return array<array{coordinates: Coordinates, distance: Distance}>
     */
    public function sortByDistance(Coordinates $from, array $coordinates): array;
}
