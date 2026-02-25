<?php

declare(strict_types=1);

namespace App\Infrastructure\Operations;

use Nexus\TenantOperations\Contracts\SettingsInitializerAdapterInterface;

final readonly class SettingsInitializerAdapter implements SettingsInitializerAdapterInterface
{
    public function initialize(string $tenantId, array $settings): void
    {
        // Simulate initializing settings in Setting package.
    }
}
