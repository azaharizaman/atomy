<?php

declare(strict_types=1);

namespace Nexus\Geo\Contracts;

use Nexus\Geo\ValueObjects\Coordinates;

/**
 * Framework-agnostic bearing calculation service interface
 * 
 * Calculates compass direction between two points
 */
interface BearingCalculatorInterface
{
    /**
     * Calculate initial bearing (forward azimuth) from origin to destination
     * 
     * @return float Bearing in degrees (0-360, where 0 = North, 90 = East)
     */
    public function calculateInitialBearing(Coordinates $from, Coordinates $to): float;

    /**
     * Calculate final bearing (back azimuth) when arriving at destination
     * 
     * @return float Bearing in degrees (0-360)
     */
    public function calculateFinalBearing(Coordinates $from, Coordinates $to): float;

    /**
     * Get compass direction from bearing
     * 
     * @return string One of: N, NE, E, SE, S, SW, W, NW
     */
    public function getCompassDirection(float $bearing): string;

    /**
     * Calculate midpoint between two coordinates
     */
    public function calculateMidpoint(Coordinates $from, Coordinates $to): Coordinates;

    /**
     * Calculate destination point given distance and bearing
     * 
     * @param Coordinates $from Starting point
     * @param float $distanceMeters Distance to travel in meters
     * @param float $bearing Bearing in degrees
     */
    public function calculateDestination(
        Coordinates $from,
        float $distanceMeters,
        float $bearing
    ): Coordinates;
}
