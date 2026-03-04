<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Workflows;

use Nexus\ConnectivityOperations\Contracts\ProviderCallPortInterface;
use Nexus\ConnectivityOperations\Contracts\ProviderCatalogPortInterface;
use Nexus\ConnectivityOperations\Contracts\ProviderHealthStoreInterface;
use Nexus\ConnectivityOperations\DataProviders\ProviderHealthDataProvider;
use Psr\Log\LoggerInterface;

final readonly class IntegrationHealthWorkflow
{
    public function __construct(
        private ProviderCatalogPortInterface $providerCatalog,
        private ProviderCallPortInterface $providerCallPort,
        private ProviderHealthStoreInterface $healthStore,
        private ProviderHealthDataProvider $healthDataProvider,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, string>
     */
    public function run(): array
    {
        foreach ($this->providerCatalog->providers() as $providerId) {
            try {
                $config = $this->providerCatalog->getConfig($providerId);
                $endpoint = is_string($config['health_endpoint'] ?? null) && trim((string) $config['health_endpoint']) !== ''
                    ? (string) $config['health_endpoint']
                    : '/health';
                $timeout = max(1, (int) ($config['health_timeout'] ?? 5));

                $this->providerCallPort->call($providerId, $endpoint, [], ['method' => 'GET', 'timeout' => $timeout]);
                try {
                    $this->healthStore->record($providerId, [
                        'status' => 'healthy',
                        'last_checked_at' => gmdate(DATE_ATOM),
                    ]);
                } catch (\Throwable $storeError) {
                    $this->logger->warning('Failed to persist healthy provider status.', [
                        'provider_id' => $providerId,
                        'error_class' => $storeError::class,
                    ]);
                }
            } catch (\Throwable $e) {
                try {
                    $this->healthStore->record($providerId, [
                        'status' => 'degraded',
                        'error' => $e->getMessage(),
                        'last_checked_at' => gmdate(DATE_ATOM),
                    ]);
                } catch (\Throwable $storeError) {
                    $this->logger->warning('Failed to persist degraded provider status.', [
                        'provider_id' => $providerId,
                        'provider_error_class' => $e::class,
                        'storage_error_class' => $storeError::class,
                    ]);
                }
            }
        }

        return $this->healthDataProvider->statuses();
    }
}
