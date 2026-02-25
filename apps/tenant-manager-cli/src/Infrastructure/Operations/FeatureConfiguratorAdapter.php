<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use Nexus\TenantOperations\Contracts\FeatureConfiguratorAdapterInterface;

final readonly class FeatureConfiguratorAdapter implements FeatureConfiguratorAdapterInterface
{
    public function configure(string $tenantId, array $features): void
    {
        // Simulate configuring features in FeatureFlags package.
    }
}
