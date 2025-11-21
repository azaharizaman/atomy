<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\GeoCache;
use App\Models\GeoRegion;
use DateTimeImmutable;
use Nexus\Geo\Contracts\GeoRepositoryInterface;
use Nexus\Geo\ValueObjects\Coordinates;
use Nexus\Geo\ValueObjects\GeocodeResult;

final readonly class DbGeoRepository implements GeoRepositoryInterface
{
    public function getCachedGeocode(string $address, string $tenantId): ?GeocodeResult
    {
        $cached = GeoCache::where('tenant_id', $tenantId)
            ->where('address', $address)
            ->where('expires_at', '>', now())
            ->first();

        if (!$cached) {
            return null;
        }

        return new GeocodeResult(
            address: $cached->address,
            coordinates: new Coordinates(
                latitude: $cached->latitude,
                longitude: $cached->longitude
            ),
            provider: $cached->provider,
            metadata: $cached->metadata ?? []
        );
    }

    public function storeCachedGeocode(GeocodeResult $result, string $tenantId, int $ttlDays): void
    {
        GeoCache::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'address' => $result->address,
            ],
            [
                'latitude' => $result->coordinates->latitude,
                'longitude' => $result->coordinates->longitude,
                'provider' => $result->provider,
                'metadata' => $result->metadata,
                'cached_at' => now(),
                'expires_at' => now()->addDays($ttlDays),
            ]
        );
    }

    public function getCacheMetrics(string $tenantId): array
    {
        $total = GeoCache::where('tenant_id', $tenantId)->count();
        $expired = GeoCache::where('tenant_id', $tenantId)
            ->where('expires_at', '<=', now())
            ->count();
        $active = $total - $expired;

        $providerStats = GeoCache::where('tenant_id', $tenantId)
            ->where('expires_at', '>', now())
            ->selectRaw('provider, COUNT(*) as count')
            ->groupBy('provider')
            ->pluck('count', 'provider')
            ->toArray();

        return [
            'total_entries' => $total,
            'active_entries' => $active,
            'expired_entries' => $expired,
            'hit_rate_estimate' => $active > 0 ? round(($active / $total) * 100, 2) : 0.0,
            'provider_breakdown' => $providerStats,
        ];
    }

    public function pruneCacheEntries(string $tenantId): int
    {
        return GeoCache::where('tenant_id', $tenantId)
            ->where('expires_at', '<=', now())
            ->delete();
    }

    public function getRegionBoundary(string $regionCode, string $tenantId): ?array
    {
        $region = GeoRegion::where('tenant_id', $tenantId)
            ->where('code', $regionCode)
            ->first();

        return $region?->boundary_polygon;
    }

    public function storeRegionBoundary(
        string $regionCode,
        string $regionName,
        array $boundaryPolygon,
        string $tenantId
    ): void {
        GeoRegion::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'code' => $regionCode,
            ],
            [
                'name' => $regionName,
                'boundary_polygon' => $boundaryPolygon,
            ]
        );
    }

    public function listRegions(string $tenantId): array
    {
        return GeoRegion::where('tenant_id', $tenantId)
            ->get()
            ->map(fn($region) => [
                'code' => $region->code,
                'name' => $region->name,
                'vertex_count' => count($region->boundary_polygon),
            ])
            ->toArray();
    }
}
