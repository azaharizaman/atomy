<?php

declare(strict_types=1);

namespace Nexus\Laravel\ConnectivityOperations\Adapters;

use Nexus\ConnectivityOperations\Contracts\ProviderCatalogPortInterface;

final class ProviderCatalogPortAdapter implements ProviderCatalogPortInterface
{
    public function providers(): array
    {
        $providers = config('connectivity_operations.providers', []);
        if (!is_array($providers)) {
            return [];
        }

        $filtered = array_filter($providers, static fn (mixed $provider): bool => is_string($provider) && trim($provider) !== '');

        return array_values(array_map(static fn (string $provider): string => trim($provider), $filtered));
    }

    public function getConfig(string $providerId): array
    {
        $allConfigs = config('connectivity_operations.provider_configs', []);
        if (!is_array($allConfigs)) {
            return [];
        }

        $config = $allConfigs[$providerId] ?? [];

        return is_array($config) ? $config : [];
    }
}
