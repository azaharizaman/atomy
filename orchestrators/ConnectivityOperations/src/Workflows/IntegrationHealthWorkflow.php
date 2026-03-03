<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Workflows;

use Nexus\ConnectivityOperations\Contracts\ProviderCallPortInterface;
use Nexus\ConnectivityOperations\Contracts\ProviderCatalogPortInterface;
use Nexus\ConnectivityOperations\Contracts\ProviderHealthStoreInterface;
use Nexus\ConnectivityOperations\DataProviders\ProviderHealthDataProvider;

final readonly class IntegrationHealthWorkflow
{
    public function __construct(
        private ProviderCatalogPortInterface $providerCatalog,
        private ProviderCallPortInterface $providerCallPort,
        private ProviderHealthStoreInterface $healthStore,
        private ProviderHealthDataProvider $healthDataProvider,
    ) {}

    /**
     * @return array<string, string>
     */
    public function run(): array
    {
        foreach ($this->providerCatalog->providers() as $providerId) {
            $config = $this->providerCatalog->getConfig($providerId);
            $endpoint = is_string($config['health_endpoint'] ?? null) && trim((string) $config['health_endpoint']) !== ''
                ? (string) $config['health_endpoint']
                : '/health';
            $timeout = max(1, (int) ($config['health_timeout'] ?? 5));

            try {
                $this->providerCallPort->call($providerId, $endpoint, [], ['method' => 'GET', 'timeout' => $timeout]);
                $this->healthStore->record($providerId, [
                    'status' => 'healthy',
                    'last_checked_at' => gmdate(DATE_ATOM),
                ]);
            } catch (\Throwable $e) {
                $this->healthStore->record($providerId, [
                    'status' => 'degraded',
                    'error' => $e->getMessage(),
                    'last_checked_at' => gmdate(DATE_ATOM),
                ]);
            }
        }

        return $this->healthDataProvider->statuses();
    }
}
