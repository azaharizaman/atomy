<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\Contracts\FeatureConfiguratorAdapterInterface;
use Psr\Log\LoggerInterface;

final readonly class LoggingFeatureConfiguratorAdapter implements FeatureConfiguratorAdapterInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function configure(string $tenantId, array $features): void
    {
        $this->logger->info('Tenant features configured in canary adapter', [
            'tenantId' => $tenantId,
            'features' => $features,
        ]);
    }
}
