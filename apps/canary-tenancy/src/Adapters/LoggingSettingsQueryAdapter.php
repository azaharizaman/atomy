<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\DataProviders\SettingsQueryInterface;
use Psr\Log\LoggerInterface;

final readonly class LoggingSettingsQueryAdapter implements SettingsQueryInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function getSettings(string $tenantId, ?string $key = null): array
    {
        $this->logger->info('Getting tenant settings in canary adapter', [
            'tenantId' => $tenantId,
            'key' => $key,
        ]);
        return [];
    }
}
