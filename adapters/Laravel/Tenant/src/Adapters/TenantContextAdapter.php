<?php

declare(strict_types=1);

namespace Nexus\Laravel\Tenant\Adapters;

use Nexus\Tenant\Contracts\TenantContextInterface;
use Nexus\Tenant\Contracts\TenantInterface;
use Nexus\Tenant\Contracts\CacheRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Laravel implementation of TenantContextInterface.
 *
 * Resolves the current tenant from Laravel's application context,
 * including request headers, authentication, or cache.
 */
class TenantContextAdapter implements TenantContextInterface
{
    private ?TenantInterface $currentTenant = null;
    private ?string $currentTenantId = null;

    public function __construct(
        private readonly CacheRepositoryInterface $cacheRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function getTenant(): ?TenantInterface
    {
        return $this->currentTenant;
    }

    /**
     * {@inheritdoc}
     */
    public function getTenantId(): ?string
    {
        return $this->currentTenantId;
    }

    /**
     * {@inheritdoc}
     */
    public function setTenant(TenantInterface $tenant): void
    {
        $this->currentTenant = $tenant;
        $this->currentTenantId = $tenant->getId();
        
        $this->logger->debug('Tenant context set', [
            'tenant_id' => $this->currentTenantId,
            'tenant_code' => $tenant->getCode(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setTenantId(string $tenantId): void
    {
        $this->currentTenantId = $tenantId;
        $this->currentTenant = null; // Will be resolved lazily
        
        $this->logger->debug('Tenant ID set in context', [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function clearTenant(): void
    {
        $this->currentTenant = null;
        $this->currentTenantId = null;
        
        $this->logger->debug('Tenant context cleared');
    }

    /**
     * {@inheritdoc}
     */
    public function hasTenant(): bool
    {
        return $this->currentTenantId !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function isGlobalMode(): bool
    {
        // In Laravel, global mode can be enabled via config
        return config('tenant.global_mode', false);
    }

    /**
     * {@inheritdoc}
     */
    public function getTenantCacheKey(string $key): string
    {
        $tenantId = $this->currentTenantId ?? 'global';
        return "tenant:{$tenantId}:{$key}";
    }
}
