<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\DataProviders\FeatureToggleInterface;
use Psr\Log\LoggerInterface;

final readonly class LoggingFeatureToggleAdapter implements FeatureToggleInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function enable(string $tenantId, string $featureKey): bool
    {
        $this->logger->info('Enabling feature in canary adapter', [
            'tenantId' => $tenantId,
            'featureKey' => $featureKey,
        ]);
        return true;
    }

    public function disable(string $tenantId, string $featureKey): bool
    {
        $this->logger->info('Disabling feature in canary adapter', [
            'tenantId' => $tenantId,
            'featureKey' => $featureKey,
        ]);
        return true;
    }
}
