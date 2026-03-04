<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Contracts;

interface ProviderCatalogPortInterface
{
    /**
     * @return array<int, string>
     */
    public function providers(): array;

    /**
     * @return array<string, mixed>
     */
    public function getConfig(string $providerId): array;
}
