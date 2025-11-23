<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Contracts;

/**
 * Cache interface for feature flag repository results.
 *
 * Subset of PSR-16 SimpleCache focused on flag storage needs:
 * - get/set operations with TTL
 * - Multiple key retrieval (getMultiple)
 * - Delete operations
 *
 * NOT included from PSR-16:
 * - has() (use get() with null default)
 * - clear() (flags are scoped, not cleared)
 * - setMultiple() (not needed - flags saved individually)
 *
 * Cache Key Format: "ff:tenant:{tenantId}:flag:{flagName}"
 * - "ff:" prefix to avoid collisions
 * - Tenant scoping for multi-tenancy
 * - Flag name for uniqueness
 *
 * Cache Value Format: Serialized FlagDefinition with checksum
 *
 * TTL Strategy:
 * - Short TTL (60-300s) to balance performance and freshness
 * - Checksum validation on every read
 * - Stale cache detection via checksum mismatch
 */
interface FlagCacheInterface
{
    /**
     * Retrieve a flag definition from cache.
     *
     * @param string $key Cache key (e.g., "ff:tenant:123:flag:new.feature")
     * @param FlagDefinitionInterface|null $default Default value if key not found
     * @return FlagDefinitionInterface|null Cached flag or default
     */
    public function get(string $key, ?FlagDefinitionInterface $default = null): ?FlagDefinitionInterface;

    /**
     * Store a flag definition in cache with TTL.
     *
     * @param string $key Cache key
     * @param FlagDefinitionInterface $value Flag definition to cache
     * @param int $ttl Time to live in seconds
     * @return bool True on success, false on failure
     */
    public function set(string $key, FlagDefinitionInterface $value, int $ttl): bool;

    /**
     * Retrieve multiple flag definitions from cache.
     *
     * @param array<string> $keys Cache keys
     * @param FlagDefinitionInterface|null $default Default for missing keys
     * @return array<string, FlagDefinitionInterface|null> Key => Flag map
     */
    public function getMultiple(array $keys, ?FlagDefinitionInterface $default = null): array;

    /**
     * Delete a flag definition from cache.
     *
     * @param string $key Cache key
     * @return bool True if deleted or didn't exist, false on error
     */
    public function delete(string $key): bool;

    /**
     * Delete multiple flag definitions from cache.
     *
     * @param array<string> $keys Cache keys
     * @return bool True if all deleted successfully, false otherwise
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * Build cache key for a flag.
     *
     * Format: "ff:tenant:{tenantId}:flag:{flagName}"
     * - Uses "global" for null tenant (system-wide flags)
     *
     * @param string $flagName Flag name
     * @param string|null $tenantId Tenant ID or null for global
     * @return string Cache key
     */
    public function buildKey(string $flagName, ?string $tenantId): string;
}
