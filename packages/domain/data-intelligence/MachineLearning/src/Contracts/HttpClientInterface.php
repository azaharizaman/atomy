<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Contracts;

use Psr\Http\Message\ResponseInterface;

/**
 * HTTP client interface for external AI provider communication
 * 
 * Wraps PSR-18 ClientInterface to provide a framework-agnostic HTTP abstraction
 * specifically tailored for ML provider API calls. This avoids direct dependency
 * on Guzzle or other specific HTTP client implementations.
 * 
 * Implementations should handle:
 * - Request timeout enforcement
 * - Connection error handling
 * - Response validation (status codes, content-type)
 * - Basic retry logic (optional, can be delegated to Nexus\Connector)
 * 
 * Example usage:
 * ```php
 * $response = $httpClient->post('https://api.openai.com/v1/completions', [
 *     'headers' => ['Authorization' => 'Bearer ' . $apiKey],
 *     'json' => ['model' => 'gpt-4', 'prompt' => 'Analyze this data...'],
 * ]);
 * ```
 */
interface HttpClientInterface
{
    /**
     * Send a GET request
     * 
     * @param string $uri The request URI
     * @param array<string, mixed> $options Request options (headers, query parameters, timeout, etc.)
     * 
     * @return ResponseInterface PSR-7 response
     * 
     * @throws \Psr\Http\Client\ClientExceptionInterface For network or protocol errors
     */
    public function get(string $uri, array $options = []): ResponseInterface;

    /**
     * Send a POST request
     * 
     * @param string $uri The request URI
     * @param array<string, mixed> $options Request options (headers, body, json, form data, timeout, etc.)
     * 
     * @return ResponseInterface PSR-7 response
     * 
     * @throws \Psr\Http\Client\ClientExceptionInterface For network or protocol errors
     */
    public function post(string $uri, array $options = []): ResponseInterface;

    /**
     * Send a PUT request
     * 
     * @param string $uri The request URI
     * @param array<string, mixed> $options Request options (headers, body, json, timeout, etc.)
     * 
     * @return ResponseInterface PSR-7 response
     * 
     * @throws \Psr\Http\Client\ClientExceptionInterface For network or protocol errors
     */
    public function put(string $uri, array $options = []): ResponseInterface;

    /**
     * Send a DELETE request
     * 
     * @param string $uri The request URI
     * @param array<string, mixed> $options Request options (headers, timeout, etc.)
     * 
     * @return ResponseInterface PSR-7 response
     * 
     * @throws \Psr\Http\Client\ClientExceptionInterface For network or protocol errors
     */
    public function delete(string $uri, array $options = []): ResponseInterface;

    /**
     * Send a request with custom HTTP method
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH, etc.)
     * @param string $uri The request URI
     * @param array<string, mixed> $options Request options
     * 
     * @return ResponseInterface PSR-7 response
     * 
     * @throws \Psr\Http\Client\ClientExceptionInterface For network or protocol errors
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface;
}
