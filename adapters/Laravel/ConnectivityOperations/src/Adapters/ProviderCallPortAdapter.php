<?php

declare(strict_types=1);

namespace Nexus\Laravel\ConnectivityOperations\Adapters;

use Nexus\ConnectivityOperations\Contracts\ProviderCallPortInterface;
use Nexus\Connector\Contracts\HttpClientInterface;
use Nexus\Connector\ValueObjects\Endpoint;
use Nexus\Connector\ValueObjects\HttpMethod;

final readonly class ProviderCallPortAdapter implements ProviderCallPortInterface
{
    public function __construct(private HttpClientInterface $httpClient) {}

    public function call(string $providerId, string $endpoint, array $payload, array $options): array
    {
        $method = HttpMethod::from(strtoupper((string) ($options['method'] ?? 'POST')));
        $headers = is_array($options['headers'] ?? null) ? $options['headers'] : [];
        $credentials = is_array($options['credentials'] ?? null) ? $options['credentials'] : [];
        $timeout = max(1, (int) ($options['timeout'] ?? 30));

        $endpointVo = new Endpoint(
            url: $endpoint,
            method: $method,
            headers: $headers,
            timeout: $timeout,
        );

        $response = $this->httpClient->send($endpointVo, $payload, $credentials);

        return [
            'provider_id' => $providerId,
            'status_code' => $response['status_code'],
            'body' => $response['body'],
            'headers' => $response['headers'],
        ];
    }
}
