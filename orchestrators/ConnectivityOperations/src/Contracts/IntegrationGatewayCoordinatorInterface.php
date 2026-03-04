<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Contracts;

interface IntegrationGatewayCoordinatorInterface
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function call(string $providerId, string $endpoint, array $payload = [], array $options = []): array;

    public function rotateSecrets(string $providerId): bool;

    /**
     * @return array<string, string>
     */
    public function checkIntegrationsHealth(): array;
}
