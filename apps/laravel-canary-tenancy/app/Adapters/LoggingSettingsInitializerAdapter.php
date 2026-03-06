<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\Contracts\SettingsInitializerAdapterInterface;
use Illuminate\Support\Facades\Log;

final readonly class LoggingSettingsInitializerAdapter implements SettingsInitializerAdapterInterface
{
    public function initialize(string $tenantId, array $settings): void
    {
        Log::info('Laravel Canary: Settings initialized', [
            'tenantId' => $tenantId,
            'settings' => $settings,
        ]);
    }
}
