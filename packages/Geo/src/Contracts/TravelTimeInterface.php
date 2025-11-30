<?php

declare(strict_types=1);

namespace Nexus\Geo\Contracts;

use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\ValueObjects\TravelTimeMatrix;

/**
 * Framework-agnostic travel time calculation service interface
 * 
 * Calculates travel time/distance matrices for routing optimization
 */
interface TravelTimeInterface
{
    /**
     * Calculate travel time matrix for multiple origins and destinations
     * 
     * @param array<string, Coordinates> $origins Map of ID => Coordinates
     * @param array<string, Coordinates> $destinations Map of ID => Coordinates
     * @param array $options Options like mode (driving/walking), traffic, etc.
     */
    public function calculateMatrix(
        array $origins,
        array $destinations,
        array $options = []
    ): TravelTimeMatrix;

    /**
     * Estimate travel time between two points
     * 
     * @param string $mode Travel mode: driving, walking, cycling
     * @return int Travel time in seconds
     */
    public function estimateTravelTime(
        Coordinates $from,
        Coordinates $to,
        string $mode = 'driving'
    ): int;

    /**
     * Get average speed for travel mode in meters per second
     * 
     * @param string $mode Travel mode: driving, walking, cycling
     */
    public function getAverageSpeed(string $mode): float;
}
