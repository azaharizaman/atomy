<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\Contracts\SettingsInitializerAdapterInterface;
use Psr\Log\LoggerInterface;

final readonly class LoggingSettingsInitializerAdapter implements SettingsInitializerAdapterInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function initialize(string $tenantId, array $settings): void
    {
        $this->logger->info('Tenant settings initialized in canary adapter', [
            'tenantId' => $tenantId,
            'settings' => $settings,
        ]);
    }
}
