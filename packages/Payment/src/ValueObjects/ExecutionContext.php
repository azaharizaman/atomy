<?php

declare(strict_types=1);

namespace Nexus\Payment\ValueObjects;

/**
 * Payment execution context value object.
 *
 * Captures contextual information about the payment execution environment
 * for auditing, fraud detection, and troubleshooting purposes.
 */
final readonly class ExecutionContext
{
    /**
     * @param \DateTimeImmutable $timestamp When the execution was initiated
     * @param string|null $ipAddress IP address of the request
     * @param string|null $userAgent User agent string from the request
     * @param string|null $userId ID of the user who initiated the execution
     * @param string|null $sessionId Session ID for correlation
     * @param string|null $deviceFingerprint Device fingerprint for fraud detection
     * @param string|null $channel Execution channel (web, api, batch, mobile)
     * @param array<string, mixed> $metadata Additional context metadata
     */
    public function __construct(
        public \DateTimeImmutable $timestamp,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $userId = null,
        public ?string $sessionId = null,
        public ?string $deviceFingerprint = null,
        public ?string $channel = null,
        public array $metadata = [],
    ) {
    }

    /**
     * Create a new execution context for the current time.
     */
    public static function now(): self
    {
        return new self(new \DateTimeImmutable());
    }

    /**
     * Create an execution context with common web request details.
     */
    public static function fromWebRequest(
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $userId = null,
    ): self {
        return new self(
            timestamp: new \DateTimeImmutable(),
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            userId: $userId,
            channel: 'web',
        );
    }

    /**
     * Create an execution context for API requests.
     */
    public static function fromApiRequest(
        ?string $ipAddress = null,
        ?string $userId = null,
        ?string $apiKeyId = null,
    ): self {
        return new self(
            timestamp: new \DateTimeImmutable(),
            ipAddress: $ipAddress,
            userId: $userId,
            channel: 'api',
            metadata: $apiKeyId !== null ? ['api_key_id' => $apiKeyId] : [],
        );
    }

    /**
     * Create an execution context for batch/scheduled jobs.
     */
    public static function fromBatchJob(string $jobId, ?string $batchId = null): self
    {
        return new self(
            timestamp: new \DateTimeImmutable(),
            channel: 'batch',
            metadata: array_filter([
                'job_id' => $jobId,
                'batch_id' => $batchId,
            ]),
        );
    }

    /**
     * Create a copy with user ID.
     */
    public function withUserId(string $userId): self
    {
        return new self(
            $this->timestamp,
            $this->ipAddress,
            $this->userAgent,
            $userId,
            $this->sessionId,
            $this->deviceFingerprint,
            $this->channel,
            $this->metadata,
        );
    }

    /**
     * Create a copy with session ID.
     */
    public function withSessionId(string $sessionId): self
    {
        return new self(
            $this->timestamp,
            $this->ipAddress,
            $this->userAgent,
            $this->userId,
            $sessionId,
            $this->deviceFingerprint,
            $this->channel,
            $this->metadata,
        );
    }

    /**
     * Create a copy with additional metadata.
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            $this->timestamp,
            $this->ipAddress,
            $this->userAgent,
            $this->userId,
            $this->sessionId,
            $this->deviceFingerprint,
            $this->channel,
            array_merge($this->metadata, $metadata),
        );
    }

    /**
     * Create a copy with additional metadata merged.
     * Alias for withMetadata for clearer intent.
     */
    public function withAdditionalMetadata(array $metadata): self
    {
        return $this->withMetadata($metadata);
    }

    /**
     * Check if the execution originated from a web request.
     */
    public function isFromWeb(): bool
    {
        return $this->channel === 'web';
    }

    /**
     * Check if the execution originated from an API request.
     */
    public function isFromApi(): bool
    {
        return $this->channel === 'api';
    }

    /**
     * Check if the execution originated from a batch job.
     */
    public function isFromBatch(): bool
    {
        return $this->channel === 'batch';
    }

    /**
     * Get a metadata value.
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Convert to array for serialization/logging.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'timestamp' => $this->timestamp->format(\DateTimeInterface::RFC3339),
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'device_fingerprint' => $this->deviceFingerprint,
            'channel' => $this->channel,
            'metadata' => $this->metadata ?: null,
        ], fn ($value) => $value !== null);
    }
}
