<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Services;

use Nexus\ConnectivityOperations\Contracts\IntegrationGatewayCoordinatorInterface;

/**
 * Compatibility facade retained for callers using src/Services namespace.
 */
final readonly class IntegrationGateway implements IntegrationGatewayCoordinatorInterface
{
    public function __construct(private \Nexus\ConnectivityOperations\Coordinators\IntegrationGateway $coordinator) {}

    public function call(string $providerId, string $endpoint, array $payload = [], array $options = []): array
    {
        return $this->coordinator->call($providerId, $endpoint, $payload, $options);
    }

    public function rotateSecrets(string $providerId): bool
    {
        return $this->coordinator->rotateSecrets($providerId);
    }

    public function checkIntegrationsHealth(): array
    {
        return $this->coordinator->checkIntegrationsHealth();
    }
}
