<?php

declare(strict_types=1);

namespace Nexus\Connector\ValueObjects;

/**
 * Immutable endpoint configuration object.
 */
final class Endpoint
{
    public readonly RetryPolicy $retryPolicy;
    public readonly ?RateLimitConfig $rateLimitConfig;

    /**
     * @param string $url Full URL or base URL for the endpoint
     * @param HttpMethod $method HTTP method
     * @param array<string, string> $headers Additional headers
     * @param int $timeout Request timeout in seconds
     * @param RetryPolicy|null $retryPolicy Retry configuration
     * @param RateLimitConfig|null $rateLimitConfig Rate limiting configuration
     */
    public function __construct(
        public readonly string $url,
        public readonly HttpMethod $method,
        public readonly array $headers = [],
        public readonly int $timeout = 30,
        ?RetryPolicy $retryPolicy = null,
        ?RateLimitConfig $rateLimitConfig = null,
    ) {
        $this->retryPolicy = $retryPolicy ?? new RetryPolicy();
        $this->rateLimitConfig = $rateLimitConfig;
    }

    /**
     * Create endpoint with URL and method.
     */
    public static function create(string $url, HttpMethod $method = HttpMethod::POST): self
    {
        return new self(url: $url, method: $method);
    }

    /**
     * Create a new endpoint with additional headers.
     */
    public function withHeaders(array $headers): self
    {
        return new self(
            url: $this->url,
            method: $this->method,
            headers: array_merge($this->headers, $headers),
            timeout: $this->timeout,
            retryPolicy: $this->retryPolicy,
            rateLimitConfig: $this->rateLimitConfig
        );
    }

    /**
     * Create a new endpoint with custom timeout.
     */
    public function withTimeout(int $timeout): self
    {
        return new self(
            url: $this->url,
            method: $this->method,
            headers: $this->headers,
            timeout: $timeout,
            retryPolicy: $this->retryPolicy,
            rateLimitConfig: $this->rateLimitConfig
        );
    }

    /**
     * Create a new endpoint with custom retry policy.
     */
    public function withRetryPolicy(RetryPolicy $retryPolicy): self
    {
        return new self(
            url: $this->url,
            method: $this->method,
            headers: $this->headers,
            timeout: $this->timeout,
            retryPolicy: $retryPolicy,
            rateLimitConfig: $this->rateLimitConfig
        );
    }

    /**
     * Create a new endpoint with rate limit configuration.
     */
    public function withRateLimit(RateLimitConfig $rateLimitConfig): self
    {
        return new self(
            url: $this->url,
            method: $this->method,
            headers: $this->headers,
            timeout: $this->timeout,
            retryPolicy: $this->retryPolicy,
            rateLimitConfig: $rateLimitConfig
        );
    }
}
