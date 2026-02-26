<?php

declare(strict_types=1);

namespace Nexus\ConnectivityOperations\Contracts;

/**
 * Interface IntegrationGatewayCoordinatorInterface
 *
 * Coordinates external third-party integrations with resilient connectivity,
 * security rotation, and operational visibility.
 */
interface IntegrationGatewayCoordinatorInterface
{
    /**
     * Executes a request to an external service.
     *
     * @param string $providerId
     * @param string $endpoint
     * @param array $payload
     * @param array $options Circuit breaker and retry settings.
     * @return array The response.
     */
    public function call(string $providerId, string $endpoint, array $payload = [], array $options = []): array;

    /**
     * Rotates API keys for a secure integration.
     *
     * @param string $providerId
     * @return bool
     */
    public function rotateSecrets(string $providerId): bool;

    /**
     * Tracks the health of all integration endpoints.
     *
     * @return array Health status list.
     */
    public function checkIntegrationsHealth(): array;
}
