<?php

declare(strict_types=1);

namespace Nexus\Laravel\Tenant\Adapters;

use Nexus\Tenant\Contracts\ImpersonationStorageInterface;
use Illuminate\Contracts\Cache\Store;
use Psr\Log\LoggerInterface;

/**
 * Laravel implementation of ImpersonationStorageInterface.
 *
 * Uses Laravel's cache system for storing impersonation tokens.
 */
class ImpersonationStorageAdapter implements ImpersonationStorageInterface
{
    private const IMPERSONATION_PREFIX = 'impersonation:';
    private const TOKEN_TTL = 3600; // 1 hour default

    public function __construct(
        private readonly Store $cache,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function store(
        string $key,
        string $originalTenantId,
        string $targetTenantId,
        ?string $impersonatorId = null
    ): void {
        $cacheKey = self::IMPERSONATION_PREFIX . $key;
        
        $this->cache->put($cacheKey, [
            'original_tenant_id' => $originalTenantId,
            'target_tenant_id' => $targetTenantId,
            'impersonator_id' => $impersonatorId,
            'created_at' => time(),
        ], self::TOKEN_TTL);
        
        $this->logger->info('Impersonation stored', [
            'key' => $key,
            'original_tenant_id' => $originalTenantId,
            'target_tenant_id' => $targetTenantId,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve(string $key): ?array
    {
        $cacheKey = self::IMPERSONATION_PREFIX . $key;
        $data = $this->cache->get($cacheKey);
        
        if ($data === null) {
            return null;
        }
        
        return [
            'original_tenant_id' => $data['original_tenant_id'] ?? null,
            'target_tenant_id' => $data['target_tenant_id'] ?? null,
            'impersonator_id' => $data['impersonator_id'] ?? null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(string $key): bool
    {
        return $this->retrieve($key) !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): void
    {
        $cacheKey = self::IMPERSONATION_PREFIX . $key;
        $this->cache->forget($cacheKey);
        
        $this->logger->info('Impersonation cleared', ['key' => $key]);
    }

    /**
     * {@inheritdoc}
     */
    public function getOriginalTenantId(string $key): ?string
    {
        $data = $this->retrieve($key);
        return $data['original_tenant_id'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetTenantId(string $key): ?string
    {
        $data = $this->retrieve($key);
        return $data['target_tenant_id'] ?? null;
    }
}
