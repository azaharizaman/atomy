<?php

declare(strict_types=1);

namespace Nexus\FeatureFlags\Core\Repository;

use Nexus\FeatureFlags\Contracts\FlagDefinitionInterface;
use Nexus\FeatureFlags\Contracts\FlagRepositoryInterface;

/**
 * In-memory implementation of flag repository for testing and standalone usage.
 *
 * Supports tenant inheritance:
 * - Tenant-specific flags (with tenantId) override global flags (null tenantId)
 * - Storage key format: "tenant:{tenantId}:flag:{flagName}" or "global:flag:{flagName}"
 */
final class InMemoryFlagRepository implements FlagRepositoryInterface
{
    /**
     * @var array<string, FlagDefinitionInterface>
     */
    private array $storage = [];

    public function find(string $name, ?string $tenantId = null): ?FlagDefinitionInterface
    {
        // Check for tenant-specific flag first
        if ($tenantId !== null) {
            $tenantKey = $this->buildKey($name, $tenantId);
            if (isset($this->storage[$tenantKey])) {
                return $this->storage[$tenantKey];
            }
        }

        // Fallback to global flag
        $globalKey = $this->buildKey($name, null);
        return $this->storage[$globalKey] ?? null;
    }

    public function findMany(array $names, ?string $tenantId = null): array
    {
        $results = [];

        foreach ($names as $name) {
            $flag = $this->find($name, $tenantId);
            if ($flag !== null) {
                $results[$name] = $flag;
            }
        }

        return $results;
    }

    public function save(FlagDefinitionInterface $flag): void
    {
        // Extract tenant ID from flag metadata if present
        $tenantId = $flag->getMetadata()['tenant_id'] ?? null;
        $key = $this->buildKey($flag->getName(), $tenantId);

        $this->storage[$key] = $flag;
    }

    public function delete(string $name, ?string $tenantId = null): void
    {
        $key = $this->buildKey($name, $tenantId);
        unset($this->storage[$key]);
    }

    public function all(?string $tenantId = null): array
    {
        if ($tenantId === null) {
            // Return all global flags
            return array_filter(
                $this->storage,
                fn($key) => str_starts_with($key, 'global:'),
                ARRAY_FILTER_USE_KEY
            );
        }

        // Return all flags for specific tenant (including inherited global flags)
        $results = [];

        // Add tenant-specific flags
        foreach ($this->storage as $key => $flag) {
            if (str_starts_with($key, "tenant:{$tenantId}:")) {
                $results[] = $flag;
            }
        }

        // Add global flags not overridden by tenant-specific ones
        $tenantFlagNames = array_map(fn($f) => $f->getName(), $results);
        foreach ($this->storage as $key => $flag) {
            if (str_starts_with($key, 'global:') && !in_array($flag->getName(), $tenantFlagNames, true)) {
                $results[] = $flag;
            }
        }

        return $results;
    }

    /**
     * Build storage key from flag name and optional tenant ID.
     *
     * @param string $name The flag name
     * @param string|null $tenantId The tenant ID or null for global
     * @return string The storage key
     */
    private function buildKey(string $name, ?string $tenantId): string
    {
        if ($tenantId === null) {
            return "global:flag:{$name}";
        }

        return "tenant:{$tenantId}:flag:{$name}";
    }
}
