<?php

declare(strict_types=1);

namespace Nexus\Laravel\Setting\Adapters;

use Nexus\Setting\Contracts\SettingRepositoryInterface;
use Nexus\Setting\Contracts\SettingsCacheInterface;
use Psr\Log\LoggerInterface;

/**
 * Laravel implementation of SettingRepositoryInterface.
 */
class SettingRepositoryAdapter implements SettingRepositoryInterface
{
    public function __construct(
        private readonly SettingsCacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $cached = $this->cache->get("setting:{$key}");
        if ($cached !== null) {
            return $cached;
        }
        
        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->cache->set("setting:{$key}", $value);
        $this->logger->debug('Setting stored', ['key' => $key]);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): void
    {
        $this->cache->forget("setting:{$key}");
        $this->logger->debug('Setting deleted', ['key' => $key]);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->cache->has("setting:{$key}");
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(): array
    {
        // Implementation would load from database
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getByPrefix(string $prefix): array
    {
        // Implementation would load from database
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(string $key): ?array
    {
        // Implementation would load from database
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function bulkSet(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }
        $this->logger->info('Bulk settings stored', ['count' => count($settings)]);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable(): bool
    {
        return true;
    }
}
