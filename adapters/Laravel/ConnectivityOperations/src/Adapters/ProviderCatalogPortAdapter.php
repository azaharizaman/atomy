<?php

declare(strict_types=1);

namespace Nexus\Laravel\ConnectivityOperations\Adapters;

use Nexus\ConnectivityOperations\Contracts\ProviderCatalogPortInterface;

final class ProviderCatalogPortAdapter implements ProviderCatalogPortInterface
{
    public function providers(): array
    {
        $providers = config('connectivity_operations.providers', []);

        return is_array($providers) ? array_values(array_map('strval', $providers)) : [];
    }
}
