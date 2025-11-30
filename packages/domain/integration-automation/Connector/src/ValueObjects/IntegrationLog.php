<?php

declare(strict_types=1);

namespace Nexus\Connector\ValueObjects;

/**
 * Immutable integration log entry.
 */
final readonly class IntegrationLog
{
    /**
     * @param string $id Unique log identifier (ULID)
     * @param string $serviceName External service name (e.g., 'mailchimp', 'twilio')
     * @param string $endpoint API endpoint called
     * @param HttpMethod $method HTTP method used
     * @param IntegrationStatus $status Request status
     * @param int|null $httpStatusCode HTTP response status code
     * @param int $durationMs Request duration in milliseconds
     * @param array<string, mixed> $requestData Sanitized request data
     * @param array<string, mixed>|null $responseData Sanitized response data
     * @param string|null $errorMessage Error message if failed
     * @param \DateTimeImmutable $timestamp When the request occurred
     * @param string|null $tenantId Tenant identifier for multi-tenancy
     * @param int $attemptNumber Retry attempt number (1 for first attempt)
     */
    public function __construct(
        public string $id,
        public string $serviceName,
        public string $endpoint,
        public HttpMethod $method,
        public IntegrationStatus $status,
        public ?int $httpStatusCode,
        public int $durationMs,
        public array $requestData,
        public ?array $responseData,
        public ?string $errorMessage,
        public \DateTimeImmutable $timestamp,
        public ?string $tenantId = null,
        public int $attemptNumber = 1,
    ) {}

    /**
     * Create a successful log entry.
     */
    public static function success(
        string $id,
        string $serviceName,
        string $endpoint,
        HttpMethod $method,
        int $httpStatusCode,
        int $durationMs,
        array $requestData = [],
        ?array $responseData = null,
        ?string $tenantId = null,
        int $attemptNumber = 1
    ): self {
        return new self(
            id: $id,
            serviceName: $serviceName,
            endpoint: $endpoint,
            method: $method,
            status: IntegrationStatus::SUCCESS,
            httpStatusCode: $httpStatusCode,
            durationMs: $durationMs,
            requestData: $requestData,
            responseData: $responseData,
            errorMessage: null,
            timestamp: new \DateTimeImmutable(),
            tenantId: $tenantId,
            attemptNumber: $attemptNumber
        );
    }

    /**
     * Create a failed log entry.
     */
    public static function failed(
        string $id,
        string $serviceName,
        string $endpoint,
        HttpMethod $method,
        string $errorMessage,
        ?int $httpStatusCode,
        int $durationMs,
        array $requestData = [],
        ?string $tenantId = null,
        int $attemptNumber = 1
    ): self {
        return new self(
            id: $id,
            serviceName: $serviceName,
            endpoint: $endpoint,
            method: $method,
            status: IntegrationStatus::FAILED,
            httpStatusCode: $httpStatusCode,
            durationMs: $durationMs,
            requestData: $requestData,
            responseData: null,
            errorMessage: $errorMessage,
            timestamp: new \DateTimeImmutable(),
            tenantId: $tenantId,
            attemptNumber: $attemptNumber
        );
    }

    /**
     * Check if the request was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === IntegrationStatus::SUCCESS;
    }

    /**
     * Check if the request failed.
     */
    public function isFailed(): bool
    {
        return $this->status !== IntegrationStatus::SUCCESS;
    }
}
