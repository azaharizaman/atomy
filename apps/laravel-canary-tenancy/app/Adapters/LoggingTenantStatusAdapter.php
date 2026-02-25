<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\DataProviders\TenantStatusQueryInterface;
use Nexus\TenantOperations\DataProviders\ConfigurationQueryInterface;
use Illuminate\Support\Facades\Log;

final readonly class LoggingTenantStatusAdapter implements TenantStatusQueryInterface, ConfigurationQueryInterface
{
    public function isActive(string $tenantId): bool
    {
        Log::info('Laravel Canary: Checking if tenant is active', ['tenantId' => $tenantId]);
        return true;
    }

    public function getStatus(string $tenantId): ?string
    {
        Log::info('Laravel Canary: Getting tenant status', ['tenantId' => $tenantId]);
        return 'ACTIVE';
    }

    public function exists(string $tenantId, string $configKey): bool
    {
        Log::info('Laravel Canary: Checking if configuration exists', [
            'tenantId' => $tenantId,
            'configKey' => $configKey,
        ]);
        return true;
    }

    public function get(string $tenantId, string $configKey): ?array
    {
        Log::info('Laravel Canary: Getting configuration', [
            'tenantId' => $tenantId,
            'configKey' => $configKey,
        ]);
        return [];
    }
}
