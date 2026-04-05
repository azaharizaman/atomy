<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\Contracts\FeatureConfiguratorAdapterInterface;
use Illuminate\Support\Facades\Log;

final readonly class LoggingFeatureConfiguratorAdapter implements FeatureConfiguratorAdapterInterface
{
    public function configure(string $tenantId, array $features): void
    {
        Log::info('Laravel Canary: Features configured', [
            'tenantId' => $tenantId,
            'features' => $features,
        ]);
    }
}
