<?php

declare(strict_types=1);

namespace Nexus\Laravel\FeatureFlags\Adapters;

use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;
use Nexus\FeatureFlags\Contracts\FlagCacheInterface;
use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Psr\Log\LoggerInterface;

/**
 * Laravel implementation of FlagRepositoryInterface.
 */
class FlagRepositoryAdapter implements FlagRepositoryInterface
{
    public function __construct(
        private readonly FlagCacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function find(string $name, ?string $tenantId = null): ?FlagDefinitionInterface
    {
        $key = $this->cache->buildKey($name, $tenantId);
        return $this->cache->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function findMany(array $names, ?string $tenantId = null): array
    {
        $keys = [];
        foreach ($names as $name) {
            $keys[$name] = $this->cache->buildKey($name, $tenantId);
        }
        
        $cached = $this->cache->getMultiple(array_values($keys));
        
        $result = [];
        foreach ($keys as $name => $key) {
            if (isset($cached[$key]) && $cached[$key] !== null) {
                $result[$name] = $cached[$key];
            }
        }
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function save(FlagDefinitionInterface $flag): void
    {
        // For save, we need to determine tenant from the flag or use global
        // This would typically come from the flag's metadata
        $tenantId = $flag->getMetadata()['tenant_id'] ?? null;
        $key = $this->cache->buildKey($flag->getName(), $tenantId);
        $this->cache->set($key, $flag, 300);
        $this->logger->debug('Flag saved', ['name' => $flag->getName()]);
    }

    /**
     * {@inheritdoc}
     */
    public function saveForTenant(FlagDefinitionInterface $flag, ?string $tenantId = null): void
    {
        $key = $this->cache->buildKey($flag->getName(), $tenantId);
        $this->cache->set($key, $flag, 300);
        $this->logger->debug('Flag saved for tenant', ['name' => $flag->getName(), 'tenant' => $tenantId]);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $name, ?string $tenantId = null): void
    {
        $key = $this->cache->buildKey($name, $tenantId);
        $this->cache->delete($key);
        $this->logger->debug('Flag deleted', ['name' => $name, 'tenant' => $tenantId]);
    }

    /**
     * {@inheritdoc}
     */
    public function all(?string $tenantId = null): array
    {
        throw new \BadMethodCallException('FlagRepositoryAdapter::all() is not yet implemented. This adapter currently supports cache-only storage.');
    }
}
