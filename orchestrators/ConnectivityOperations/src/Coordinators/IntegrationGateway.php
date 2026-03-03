<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Coordinators;

use Nexus\ConnectivityOperations\Contracts\IntegrationGatewayCoordinatorInterface;
use Nexus\ConnectivityOperations\DTOs\ProviderCallRequest;
use Nexus\ConnectivityOperations\Workflows\IntegrationHealthWorkflow;
use Nexus\ConnectivityOperations\Workflows\ProviderCallWorkflow;
use Nexus\ConnectivityOperations\Workflows\SecretRotationWorkflow;
use Psr\Log\LoggerInterface;

final readonly class IntegrationGateway implements IntegrationGatewayCoordinatorInterface
{
    public function __construct(
        private ProviderCallWorkflow $providerCallWorkflow,
        private SecretRotationWorkflow $secretRotationWorkflow,
        private IntegrationHealthWorkflow $integrationHealthWorkflow,
        private LoggerInterface $logger,
    ) {}

    public function call(string $providerId, string $endpoint, array $payload = [], array $options = []): array
    {
        $response = $this->providerCallWorkflow->run(new ProviderCallRequest(
            providerId: $providerId,
            endpoint: $endpoint,
            payload: $payload,
            options: $options,
        ));

        $this->logger->info('Integration provider call succeeded.', [
            'provider_id' => $providerId,
            'endpoint' => $endpoint,
        ]);

        return $response;
    }

    public function rotateSecrets(string $providerId): bool
    {
        $rotated = $this->secretRotationWorkflow->run($providerId);

        $this->logger->info('Integration secret rotation executed.', [
            'provider_id' => $providerId,
            'rotated' => $rotated,
        ]);

        return $rotated;
    }

    public function checkIntegrationsHealth(): array
    {
        $health = $this->integrationHealthWorkflow->run();

        $this->logger->info('Integration health check completed.', [
            'providers' => array_keys($health),
        ]);

        return $health;
    }
}
