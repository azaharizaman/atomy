<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\DataProviders\SettingsQueryInterface;
use Illuminate\Support\Facades\Log;

final readonly class LoggingSettingsQueryAdapter implements SettingsQueryInterface
{
    public function getSettings(string $tenantId, ?string $key = null): array
    {
        Log::info('Laravel Canary: Getting settings', [
            'tenantId' => $tenantId,
            'key' => $key,
        ]);
        return [];
    }
}
