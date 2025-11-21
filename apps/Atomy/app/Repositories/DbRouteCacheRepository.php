<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\RouteCache;
use Nexus\Routing\Contracts\RouteCacheInterface;
use Nexus\Routing\ValueObjects\OptimizedRoute;

final readonly class DbRouteCacheRepository implements RouteCacheInterface
{
    public function getCachedRoute(string $cacheKey, string $tenantId): ?OptimizedRoute
    {
        $cached = RouteCache::where('tenant_id', $tenantId)
            ->where('cache_key', $cacheKey)
            ->where('expires_at', '>', now())
            ->first();

        if (!$cached) {
            return null;
        }

        // Decompress the route data
        $decompressed = gzdecode($cached->compressed_route);
        if ($decompressed === false) {
            return null;
        }

        $data = json_decode($decompressed, true);
        if (!$data) {
            return null;
        }

        // Reconstruct OptimizedRoute from cached data
        return OptimizedRoute::fromArray($data);
    }

    public function storeCachedRoute(
        string $cacheKey,
        OptimizedRoute $route,
        string $tenantId,
        int $ttlMinutes
    ): void {
        // Serialize and compress the route
        $serialized = json_encode($route->toArray());
        $compressed = gzencode($serialized, 9); // Maximum compression

        RouteCache::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'cache_key' => $cacheKey,
            ],
            [
                'compressed_route' => $compressed,
                'size_bytes' => strlen($serialized),
                'compressed_size_bytes' => strlen($compressed),
                'created_at' => now(),
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]
        );
    }

    public function getCacheMetrics(string $tenantId): array
    {
        $stats = RouteCache::where('tenant_id', $tenantId)
            ->selectRaw('
                COUNT(*) as total_entries,
                SUM(CASE WHEN expires_at > NOW() THEN 1 ELSE 0 END) as active_entries,
                SUM(size_bytes) as total_size_bytes,
                SUM(compressed_size_bytes) as total_compressed_bytes,
                AVG(size_bytes) as avg_size_bytes,
                AVG(compressed_size_bytes) as avg_compressed_bytes
            ')
            ->first();

        $compressionRatio = $stats->total_size_bytes > 0
            ? round((1 - ($stats->total_compressed_bytes / $stats->total_size_bytes)) * 100, 2)
            : 0.0;

        return [
            'total_entries' => $stats->total_entries ?? 0,
            'active_entries' => $stats->active_entries ?? 0,
            'total_size_mb' => round(($stats->total_size_bytes ?? 0) / 1048576, 2),
            'compressed_size_mb' => round(($stats->total_compressed_bytes ?? 0) / 1048576, 2),
            'compression_ratio_percent' => $compressionRatio,
            'avg_route_size_kb' => round(($stats->avg_size_bytes ?? 0) / 1024, 2),
            'avg_compressed_kb' => round(($stats->avg_compressed_bytes ?? 0) / 1024, 2),
        ];
    }

    public function pruneCacheEntries(string $tenantId): int
    {
        return RouteCache::where('tenant_id', $tenantId)
            ->where('expires_at', '<=', now())
            ->delete();
    }

    public function clearAllCache(string $tenantId): int
    {
        return RouteCache::where('tenant_id', $tenantId)->delete();
    }
}
