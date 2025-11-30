<?php

declare(strict_types=1);

namespace Nexus\Geo\Services;

use Nexus\Geo\Contracts\GeofenceInterface;
use Nexus\Geo\Contracts\GeoRepositoryInterface;
use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\ValueObjects\BoundingBox;
use Psr\Log\LoggerInterface;

/**
 * Stateless geofencing manager
 * 
 * Determines if coordinates are within defined regions
 */
final readonly class GeofenceManager
{
    public function __construct(
        private GeofenceInterface $geofence,
        private GeoRepositoryInterface $repository,
        private LoggerInterface $logger,
        private string $tenantId
    ) {
    }

    /**
     * Check if coordinates are within any defined region
     * 
     * @return array<string> Array of region IDs containing the coordinates
     */
    public function findContainingRegions(Coordinates $coordinates): array
    {
        $regionIds = $this->geofence->findContainingRegions($coordinates, $this->tenantId);
        
        $this->logger->debug('Geofence check', [
            'coordinates' => $coordinates->toString(),
            'regions_found' => count($regionIds),
        ]);

        return $regionIds;
    }

    /**
     * Check if coordinates are within specific polygon
     */
    public function isWithinPolygon(Coordinates $coordinates, array $polygon): bool
    {
        return $this->geofence->isWithinPolygon($coordinates, $polygon);
    }

    /**
     * Check if coordinates are within bounding box
     */
    public function isWithinBoundingBox(Coordinates $coordinates, BoundingBox $box): bool
    {
        return $this->geofence->isWithinBoundingBox($coordinates, $box);
    }

    /**
     * Check if coordinates are within radius
     */
    public function isWithinRadius(
        Coordinates $coordinates,
        Coordinates $center,
        float $radiusMeters
    ): bool {
        return $this->geofence->isWithinRadius($coordinates, $center, $radiusMeters);
    }

    /**
     * Get region details for coordinates
     * 
     * @return array<array>
     */
    public function getRegionsAt(Coordinates $coordinates): array
    {
        $regionIds = $this->findContainingRegions($coordinates);
        
        if (empty($regionIds)) {
            return [];
        }

        return array_map(
            fn(string $id) => $this->repository->getRegionById($id),
            $regionIds
        );
    }

    /**
     * Get closest point on region boundary
     */
    public function getClosestPointOnRegion(
        Coordinates $coordinates,
        string $regionId
    ): ?Coordinates {
        $region = $this->repository->getRegionById($regionId);
        
        if ($region === null || !isset($region['boundary_polygon'])) {
            return null;
        }

        return $this->geofence->getClosestPointOnPolygon(
            $coordinates,
            $region['boundary_polygon']
        );
    }
}
