<?php

declare(strict_types=1);

namespace Nexus\Tenant\Services;

use Nexus\Tenant\Contracts\CacheRepositoryInterface;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\Tenant\Contracts\TenantInterface;
use Nexus\Tenant\Contracts\TenantQueryInterface;
use Nexus\Tenant\Exceptions\TenantContextNotSetException;
use Nexus\Tenant\Exceptions\TenantNotFoundException;
use Nexus\Tenant\Exceptions\TenantSuspendedException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Tenant Context Manager
 *
 * Core service for managing the current tenant context within a request or process.
 * This is the primary service that other packages will use to access tenant information.
 *
 * Uses TenantQueryInterface (ISP-compliant) instead of fat repository.
 *
 * Note: This service maintains request-scoped state (currentTenantId), which is acceptable.
 * Unlike TenantImpersonationService, this state is ephemeral per-request and not persistent.
 *
 * @package Nexus\Tenant\Services
 */
final class TenantContextManager implements TenantContextInterface
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_PREFIX = 'tenant:';

    private ?string $currentTenantId = null;
    private ?TenantInterface $currentTenant = null;

    public function __construct(
        private readonly TenantQueryInterface $query,
        private readonly CacheRepositoryInterface $cache,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * Set the current active tenant.
     *
     * @param string $tenantId
     * @return void
     * @throws TenantNotFoundException
     * @throws TenantSuspendedException
     */
    public function setTenant(string $tenantId): void
    {
        // Load tenant from cache or database
        $tenant = $this->loadTenant($tenantId);

        if (!$tenant) {
            throw TenantNotFoundException::byId($tenantId);
        }

        // Validate tenant status
        if ($tenant->isSuspended()) {
            $this->logger->warning("Attempt to access suspended tenant: {$tenantId}");
            throw TenantSuspendedException::cannotAccess($tenantId);
        }

        $this->currentTenantId = $tenantId;
        $this->currentTenant = $tenant;

        $this->logger->info("Tenant context set: {$tenantId}");
    }

    /**
     * Get the current active tenant ID.
     *
     * @return string|null
     */
    public function getCurrentTenantId(): ?string
    {
        return $this->currentTenantId;
    }

    /**
     * Check if a tenant context is currently set.
     *
     * @return bool
     */
    public function hasTenant(): bool
    {
        return $this->currentTenantId !== null;
    }

    /**
     * Get the current active tenant entity.
     *
     * @return TenantInterface|null
     */
    public function getCurrentTenant(): ?TenantInterface
    {
        if ($this->currentTenant === null && $this->currentTenantId !== null) {
            $this->currentTenant = $this->loadTenant($this->currentTenantId);
        }

        return $this->currentTenant;
    }

    /**
     * Clear the current tenant context.
     *
     * @return void
     */
    public function clearTenant(): void
    {
        $this->currentTenantId = null;
        $this->currentTenant = null;

        $this->logger->info('Tenant context cleared');
    }

    /**
     * Require that a tenant context is set, throw exception if not.
     *
     * @return string The current tenant ID
     * @throws TenantContextNotSetException
     */
    public function requireTenant(): string
    {
        if (!$this->hasTenant()) {
            throw TenantContextNotSetException::required();
        }

        return $this->currentTenantId;
    }

    /**
     * Load tenant from cache or database.
     *
     * @param string $tenantId
     * @return TenantInterface|null
     */
    private function loadTenant(string $tenantId): ?TenantInterface
    {
        $cacheKey = self::CACHE_PREFIX . $tenantId;

        // Try to get from cache first
        $tenant = $this->cache->get($cacheKey);

        if ($tenant instanceof TenantInterface) {
            $this->logger->debug("Tenant loaded from cache: {$tenantId}");
            return $tenant;
        }

        // Load from database using query interface
        $tenant = $this->query->findById($tenantId);

        if ($tenant) {
            // Cache the tenant
            $this->cache->set($cacheKey, $tenant, self::CACHE_TTL);
            $this->logger->debug("Tenant loaded from database: {$tenantId}");
        }

        return $tenant;
    }

    /**
     * Refresh the cached tenant data.
     *
     * @param string $tenantId
     * @return void
     */
    public function refreshTenantCache(string $tenantId): void
    {
        $cacheKey = self::CACHE_PREFIX . $tenantId;
        $this->cache->forget($cacheKey);

        $tenant = $this->query->findById($tenantId);
        if ($tenant) {
            $this->cache->set($cacheKey, $tenant, self::CACHE_TTL);
            $this->logger->debug("Tenant cache refreshed: {$tenantId}");
        }
    }

    /**
     * Clear all tenant caches.
     *
     * @return void
     */
    public function clearAllTenantCaches(): void
    {
        // Note: This is a simplified implementation.
        // A production implementation might need a more sophisticated approach
        // to selectively clear tenant-specific cache keys.
        $this->cache->flush();
        $this->logger->info('All tenant caches cleared');
    }
}
