<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Core\Decorators;

use Nexus\FeatureFlags\Contracts\FlagCacheInterface;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\FeatureFlags\Exceptions\StaleCacheException;
use Psr\Log\LoggerInterface;

/**
 * Caching decorator for FlagRepositoryInterface.
 *
 * Implements cache-aside pattern with checksum-based staleness detection:
 * 1. Read: Check cache first, fallback to repository
 * 2. Write: Update repository, invalidate cache
 * 3. Validation: Compare checksums on every cache read
 *
 * Staleness Detection:
 * - If cached flag's checksum differs from expected, flag is stale
 * - Stale flags are evicted from cache
 * - Fresh flag is fetched from repository
 *
 * Cache TTL Strategy:
 * - Default: 300 seconds (5 minutes)
 * - Configurable via constructor
 * - Balance between performance and freshness
 *
 * Thread-Safety: Depends on underlying cache implementation
 */
final readonly class CachedFlagRepository implements FlagRepositoryInterface
{
    public function __construct(
        private FlagRepositoryInterface $inner,
        private FlagCacheInterface $cache,
        private LoggerInterface $logger,
        private int $ttl = 300
    ) {
    }

    public function find(string $name, ?string $tenantId = null): ?FlagDefinitionInterface
    {
        $cacheKey = $this->cache->buildKey($name, $tenantId);

        // Try cache first
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            // Validate checksum (staleness detection)
            try {
                $this->validateChecksum($cached);

                $this->logger->debug('Feature flag cache hit', [
                    'flag' => $name,
                    'tenant_id' => $tenantId,
                ]);

                return $cached;
            } catch (StaleCacheException $e) {
                // Stale cache - evict and fallback to repository
                $this->cache->delete($cacheKey);

                $this->logger->warning('Stale feature flag cache evicted', [
                    'flag' => $name,
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Cache miss or stale - fetch from repository
        $flag = $this->inner->find($name, $tenantId);

        if ($flag !== null) {
            $this->cache->set($cacheKey, $flag, $this->ttl);

            $this->logger->debug('Feature flag cached', [
                'flag' => $name,
                'tenant_id' => $tenantId,
                'ttl' => $this->ttl,
            ]);
        }

        return $flag;
    }

    public function findMany(array $names, ?string $tenantId = null): array
    {
        if (empty($names)) {
            return [];
        }

        // Build cache keys
        $cacheKeys = array_map(
            fn(string $name) => $this->cache->buildKey($name, $tenantId),
            $names
        );

        // Try cache first
        $cached = $this->cache->getMultiple($cacheKeys);

        $foundFlags = [];
        $missingNames = [];
        $staleKeys = [];

        foreach ($names as $name) {
            $cacheKey = $this->cache->buildKey($name, $tenantId);
            $flag = $cached[$cacheKey] ?? null;

            if ($flag !== null) {
                try {
                    $this->validateChecksum($flag);
                    $foundFlags[$name] = $flag;
                } catch (StaleCacheException $e) {
                    // Mark as stale for eviction
                    $staleKeys[] = $cacheKey;
                    $missingNames[] = $name;

                    $this->logger->warning('Stale feature flag in bulk cache', [
                        'flag' => $name,
                        'tenant_id' => $tenantId,
                    ]);
                }
            } else {
                $missingNames[] = $name;
            }
        }

        // Evict stale entries
        if (!empty($staleKeys)) {
            $this->cache->deleteMultiple($staleKeys);
        }

        // Fetch missing/stale flags from repository
        if (!empty($missingNames)) {
            $fetched = $this->inner->findMany($missingNames, $tenantId);

            // Cache newly fetched flags
            foreach ($fetched as $name => $flag) {
                $cacheKey = $this->cache->buildKey($name, $tenantId);
                $this->cache->set($cacheKey, $flag, $this->ttl);

                $foundFlags[$name] = $flag;
            }

            $this->logger->debug('Bulk feature flags cached', [
                'flags' => array_keys($fetched),
                'tenant_id' => $tenantId,
                'ttl' => $this->ttl,
            ]);
        }

        return $foundFlags;
    }

    public function save(FlagDefinitionInterface $flag): void
    {
        // Update repository first
        $this->inner->save($flag);

        // Invalidate cache for all tenants (global + specific)
        $this->invalidateFlag($flag->getName());

        $this->logger->info('Feature flag saved and cache invalidated', [
            'flag' => $flag->getName(),
        ]);
    }

    public function saveForTenant(FlagDefinitionInterface $flag, ?string $tenantId = null): void
    {
        // Update repository first
        $this->inner->saveForTenant($flag, $tenantId);

        // Invalidate cache for this specific tenant and global
        $this->invalidateFlagForTenant($flag->getName(), $tenantId);

        $this->logger->info('Feature flag saved for tenant and cache invalidated', [
            'flag' => $flag->getName(),
            'tenant_id' => $tenantId,
        ]);
    }

    public function delete(string $name, ?string $tenantId = null): void
    {
        // Delete from repository
        $this->inner->delete($name, $tenantId);

        // Invalidate cache
        $this->invalidateFlagForTenant($name, $tenantId);

        $this->logger->info('Feature flag deleted and cache invalidated', [
            'flag' => $name,
            'tenant_id' => $tenantId,
        ]);
    }

    public function all(?string $tenantId = null): array
    {
        // Do not cache full listings (too large, rarely used)
        return $this->inner->all($tenantId);
    }

    /**
     * Validate cached flag checksum.
     *
     * Throws StaleCacheException if checksum is invalid.
     *
     * @param FlagDefinitionInterface $flag Cached flag
     * @throws StaleCacheException If checksum validation fails
     */
    private function validateChecksum(FlagDefinitionInterface $flag): void
    {
        $stored = $flag->getChecksum();
        $computed = $this->computeChecksum($flag);

        if ($stored !== $computed) {
            throw StaleCacheException::checksumMismatch(
                $flag->getName(),
                $stored,
                $computed
            );
        }
    }

    /**
     * Compute checksum for a flag definition.
     *
     * Must match FlagDefinition::calculateChecksum() logic.
     *
     * @param FlagDefinitionInterface $flag Flag definition
     * @return string SHA-256 checksum
     */
    private function computeChecksum(FlagDefinitionInterface $flag): string
    {
        $data = [
            'enabled' => $flag->isEnabled(),
            'strategy' => $flag->getStrategy()->value,
            'value' => $flag->getValue(),
            'override' => $flag->getOverride()?->value,
        ];

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * Invalidate cache for a flag across all tenants.
     *
     * Deletes:
     * - Global flag (tenant_id = null)
     * - Note: Tenant-specific flags cannot be easily invalidated without tenant list
     *
     * For production: Consider implementing a cache tag/prefix invalidation
     * strategy or maintaining a list of active tenants.
     *
     * @param string $name Flag name
     */
    private function invalidateFlag(string $name): void
    {
        // Invalidate global flag
        $globalKey = $this->cache->buildKey($name, null);
        $this->cache->delete($globalKey);

        // Note: Cannot invalidate all tenant-specific flags without tenant registry
        // Alternative approaches:
        // 1. Cache tag invalidation (if cache supports it)
        // 2. Maintain tenant registry and iterate
        // 3. Use short TTL and accept staleness window

        $this->logger->debug('Cache invalidated for flag', [
            'flag' => $name,
            'keys_deleted' => ['global'],
        ]);
    }

    /**
     * Invalidate cache for a flag for a specific tenant.
     *
     * Deletes:
     * - Tenant-specific flag (tenant_id = $tenantId)
     * - Also invalidates global flag for consistency
     *
     * @param string $name Flag name
     * @param string|null $tenantId Tenant ID or null for global
     */
    private function invalidateFlagForTenant(string $name, ?string $tenantId): void
    {
        // Invalidate global flag
        $globalKey = $this->cache->buildKey($name, null);
        $this->cache->delete($globalKey);

        // Invalidate tenant-specific flag if applicable
        if ($tenantId !== null) {
            $tenantKey = $this->cache->buildKey($name, $tenantId);
            $this->cache->delete($tenantKey);
        }

        $this->logger->debug('Cache invalidated for flag', [
            'flag' => $name,
            'tenant_id' => $tenantId,
            'keys_deleted' => $tenantId !== null ? ['global', 'tenant'] : ['global'],
        ]);
    }
}
