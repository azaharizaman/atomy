<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\DataProviders\TenantStatusQueryInterface;
use Nexus\TenantOperations\DataProviders\ConfigurationQueryInterface;
use Psr\Log\LoggerInterface;

final readonly class LoggingTenantStatusAdapter implements TenantStatusQueryInterface, ConfigurationQueryInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function isActive(string $tenantId): bool
    {
        $this->logger->info('Checking if tenant is active in canary adapter', ['tenantId' => $tenantId]);
        return true;
    }

    public function getStatus(string $tenantId): ?string
    {
        $this->logger->info('Getting tenant status in canary adapter', ['tenantId' => $tenantId]);
        return 'ACTIVE';
    }

    public function exists(string $tenantId, string $configKey): bool
    {
        $this->logger->info('Checking if configuration exists in canary adapter', [
            'tenantId' => $tenantId,
            'configKey' => $configKey,
        ]);
        return true;
    }

    public function get(string $tenantId, string $configKey): ?array
    {
        $this->logger->info('Getting configuration in canary adapter', [
            'tenantId' => $tenantId,
            'configKey' => $configKey,
        ]);
        return [];
    }
}
