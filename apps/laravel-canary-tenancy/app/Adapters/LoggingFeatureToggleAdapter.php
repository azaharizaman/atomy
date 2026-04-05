<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\DataProviders\FeatureToggleInterface;
use Illuminate\Support\Facades\Log;

final readonly class LoggingFeatureToggleAdapter implements FeatureToggleInterface
{
    public function enable(string $tenantId, string $featureKey): bool
    {
        Log::info('Laravel Canary: Enabling feature', [
            'tenantId' => $tenantId,
            'featureKey' => $featureKey,
        ]);
        return true;
    }

    public function disable(string $tenantId, string $featureKey): bool
    {
        Log::info('Laravel Canary: Disabling feature', [
            'tenantId' => $tenantId,
            'featureKey' => $featureKey,
        ]);
        return true;
    }
}
