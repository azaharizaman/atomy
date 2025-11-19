<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\{ConnectException, RequestException};
use Nexus\Connector\Contracts\HttpClientInterface;
use Nexus\Connector\Exceptions\ConnectionException;
use Nexus\Connector\ValueObjects\{Endpoint, HttpMethod};

/**
 * Guzzle-based HTTP client implementation with timeout enforcement.
 */
final readonly class GuzzleHttpClient implements HttpClientInterface
{
    public function __construct(
        private ?Client $client = null
    ) {}

    /**
     * Send an HTTP request with timeout enforcement.
     *
     * @param Endpoint $endpoint Endpoint configuration
     * @param array<string, mixed> $payload Request payload
     * @param array<string, mixed> $credentials Authentication credentials
     * @return array{status_code: int, headers: array<string, string[]>, body: array<string, mixed>}
     * @throws ConnectionException
     */
    public function send(Endpoint $endpoint, array $payload, array $credentials): array
    {
        $client = $this->client ?? new Client();

        $options = [
            'timeout' => $endpoint->timeout,
            'connect_timeout' => min(5.0, $endpoint->timeout / 2),
            'headers' => array_merge($endpoint->headers, $this->buildAuthHeaders($credentials)),
        ];

        // Add request body based on method
        if ($endpoint->method !== HttpMethod::GET && !empty($payload)) {
            if (isset($endpoint->headers['Content-Type']) && 
                str_contains($endpoint->headers['Content-Type'], 'application/json')) {
                $options['json'] = $payload;
            } else {
                $options['form_params'] = $payload;
            }
        }

        // Add query parameters for GET requests
        if ($endpoint->method === HttpMethod::GET && !empty($payload)) {
            $options['query'] = $payload;
        }

        try {
            $response = $client->request(
                $endpoint->method->value,
                $endpoint->url,
                $options
            );

            $body = json_decode((string) $response->getBody(), true) ?? [];

            return [
                'status_code' => $response->getStatusCode(),
                'headers' => $response->getHeaders(),
                'body' => is_array($body) ? $body : ['response' => $body],
            ];

        } catch (ConnectException $e) {
            throw ConnectionException::timeout(
                $endpoint->url,
                $endpoint->timeout,
                $e
            );
        } catch (RequestException $e) {
            $statusCode = $e->getResponse()?->getStatusCode();
            $responseBody = $e->getResponse() 
                ? (string) $e->getResponse()->getBody() 
                : null;

            throw ConnectionException::requestFailed(
                message: "Request to {$endpoint->url} failed: {$e->getMessage()}" . 
                    ($responseBody ? " Response: {$responseBody}" : ""),
                httpStatusCode: $statusCode,
                previous: $e
            );
        }
    }

    /**
     * Build authentication headers from credentials.
     *
     * @param array<string, mixed> $credentials
     * @return array<string, string>
     */
    private function buildAuthHeaders(array $credentials): array
    {
        $headers = [];

        // Bearer token authentication
        if (isset($credentials['access_token'])) {
            $headers['Authorization'] = "Bearer {$credentials['access_token']}";
        }

        // Basic authentication
        if (isset($credentials['username']) && isset($credentials['password'])) {
            $encoded = base64_encode("{$credentials['username']}:{$credentials['password']}");
            $headers['Authorization'] = "Basic {$encoded}";
        }

        // API key in header
        if (isset($credentials['api_key']) && isset($credentials['api_key_header'])) {
            $headers[$credentials['api_key_header']] = $credentials['api_key'];
        }

        return $headers;
    }
}
