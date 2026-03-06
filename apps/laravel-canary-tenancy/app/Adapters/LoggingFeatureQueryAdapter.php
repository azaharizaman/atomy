<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\DataProviders\FeatureQueryInterface;
use Illuminate\Support\Facades\Log;

final readonly class LoggingFeatureQueryAdapter implements FeatureQueryInterface
{
    public function isEnabled(string $tenantId, string $featureKey): bool
    {
        Log::info('Laravel Canary: Checking if feature is enabled', [
            'tenantId' => $tenantId,
            'featureKey' => $featureKey,
        ]);
        return true;
    }

    public function getAll(string $tenantId): array
    {
        Log::info('Laravel Canary: Getting all features', ['tenantId' => $tenantId]);
        return [];
    }
}
