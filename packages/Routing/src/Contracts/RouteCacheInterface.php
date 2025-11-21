<?php

declare(strict_types=1);

namespace Nexus\Routing\Contracts;

use Nexus\Routing\ValueObjects\OptimizedRoute;

/**
 * Framework-agnostic route cache repository interface
 * 
 * Handles offline route caching with compression
 */
interface RouteCacheInterface
{
    /**
     * Store optimized route in cache
     */
    public function store(
        string $routeId,
        OptimizedRoute $route,
        string $tenantId,
        int $ttlDays = 30
    ): void;

    /**
     * Retrieve cached route
     */
    public function retrieve(string $routeId, string $tenantId): ?OptimizedRoute;

    /**
     * Check if route exists in cache
     */
    public function exists(string $routeId, string $tenantId): bool;

    /**
     * Delete cached route
     */
    public function delete(string $routeId, string $tenantId): void;

    /**
     * Get all cached route IDs for tenant
     * 
     * @return array<string>
     */
    public function getCachedRouteIds(string $tenantId): array;

    /**
     * Get cache metrics
     * 
     * @return array{total_routes: int, total_size_bytes: int, oldest_cache: ?\DateTimeImmutable}
     */
    public function getMetrics(string $tenantId): array;

    /**
     * Prune expired cache entries
     */
    public function pruneExpired(): int;

    /**
     * Clear all cache for tenant (use with caution)
     */
    public function clearTenantCache(string $tenantId): int;

    /**
     * Get cache size in bytes for tenant
     */
    public function getCacheSize(string $tenantId): int;

    /**
     * Check if cache size exceeds limit
     */
    public function exceedsSizeLimit(string $tenantId, int $limitBytes): bool;
}
