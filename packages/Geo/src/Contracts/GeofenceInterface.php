<?php

declare(strict_types=1);

namespace Nexus\Geo\Contracts;

use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\ValueObjects\BoundingBox;

/**
 * Framework-agnostic geofencing service interface
 * 
 * Determines if coordinates are within defined regions/polygons
 */
interface GeofenceInterface
{
    /**
     * Check if coordinates are within a polygon
     * 
     * @param array $polygon Array of [lat, lng] coordinate pairs
     */
    public function isWithinPolygon(Coordinates $coordinates, array $polygon): bool;

    /**
     * Check if coordinates are within a bounding box
     */
    public function isWithinBoundingBox(Coordinates $coordinates, BoundingBox $box): bool;

    /**
     * Check if coordinates are within a circular radius
     * 
     * @param float $radiusMeters Radius in meters
     */
    public function isWithinRadius(
        Coordinates $coordinates,
        Coordinates $center,
        float $radiusMeters
    ): bool;

    /**
     * Find all regions from repository that contain the coordinates
     * 
     * @return array<string> Array of region IDs
     */
    public function findContainingRegions(Coordinates $coordinates, string $tenantId): array;

    /**
     * Calculate closest point on polygon to coordinates
     */
    public function getClosestPointOnPolygon(Coordinates $coordinates, array $polygon): Coordinates;
}
