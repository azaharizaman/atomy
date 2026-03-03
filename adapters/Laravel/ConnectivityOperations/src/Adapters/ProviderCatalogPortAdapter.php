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

        return array_values(array_filter($providers, static fn (mixed $provider): bool => is_string($provider) && trim($provider) !== ''));
    }
}
