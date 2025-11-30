<?php

declare(strict_types=1);

namespace Nexus\Geo\Contracts;

use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\ValueObjects\BoundingBox;
use Nexus\Geo\ValueObjects\GeocodeResult;
use Nexus\Geo\ValueObjects\GeoMetrics;

/**
 * Framework-agnostic repository for geocoding cache and regions
 * 
 * Implementations must handle tenant isolation
 */
interface GeoRepositoryInterface
{
    /**
     * Get cached geocode result by address
     */
    public function getCachedGeocode(string $address, string $tenantId): ?GeocodeResult;

    /**
     * Store geocode result in cache
     */
    public function cacheGeocode(
        string $address,
        GeocodeResult $result,
        string $tenantId,
        int $ttlDays = 90
    ): void;

    /**
     * Get region by code
     */
    public function getRegionByCode(string $code, string $tenantId): ?array;

    /**
     * Get region by ID
     */
    public function getRegionById(string $id): ?array;

    /**
     * Get all regions for tenant
     * 
     * @return array<array>
     */
    public function getRegionsForTenant(string $tenantId): array;

    /**
     * Find regions containing coordinates
     * 
     * @return array<array>
     */
    public function findRegionsContaining(Coordinates $coordinates, string $tenantId): array;

    /**
     * Create or update region
     */
    public function saveRegion(
        string $code,
        string $name,
        array $boundaryPolygon,
        string $tenantId,
        ?string $id = null
    ): string;

    /**
     * Delete region
     */
    public function deleteRegion(string $id): void;

    /**
     * Get geocoding metrics for period
     */
    public function getMetrics(
        string $tenantId,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): GeoMetrics;

    /**
     * Prune expired cache entries
     */
    public function pruneExpiredCache(): int;
}
