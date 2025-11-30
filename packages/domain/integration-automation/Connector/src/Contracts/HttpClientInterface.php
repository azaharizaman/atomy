<?php

declare(strict_types=1);

namespace Nexus\Connector\Contracts;

use Nexus\Connector\ValueObjects\Endpoint;

/**
 * Contract for HTTP client implementation.
 *
 * This interface must be implemented by the application layer
 * using Guzzle, cURL, or any HTTP client library.
 */
interface HttpClientInterface
{
    /**
     * Send HTTP request to external endpoint.
     *
     * @param Endpoint $endpoint Endpoint configuration
     * @param array<string, mixed> $payload Request payload
     * @param array<string, mixed> $credentials Credentials for authentication
     * @return array{status_code: int, body: array<string, mixed>, headers: array<string, string>}
     * @throws \Nexus\Connector\Exceptions\ConnectionException
     */
    public function send(
        Endpoint $endpoint,
        array $payload,
        array $credentials
    ): array;
}
