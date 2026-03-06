<?php

declare(strict_types=1);

namespace App\Adapters;

use Nexus\TenantOperations\DataProviders\FeatureQueryInterface;
use Psr\Log\LoggerInterface;

final readonly class LoggingFeatureQueryAdapter implements FeatureQueryInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function isEnabled(string $tenantId, string $featureKey): bool
    {
        $this->logger->info('Checking if feature is enabled in canary adapter', [
            'tenantId' => $tenantId,
            'featureKey' => $featureKey,
        ]);
        return true;
    }

    public function getAll(string $tenantId): array
    {
        $this->logger->info('Getting all features in canary adapter', ['tenantId' => $tenantId]);
        return [];
    }
}
