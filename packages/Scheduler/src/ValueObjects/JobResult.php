<?php

declare(strict_types=1);

namespace Nexus\Scheduler\ValueObjects;

use DateTimeImmutable;

/**
 * Job Result Value Object
 *
 * Represents the outcome of a job execution.
 * Contains retry intent signaled by the handler.
 * Immutable value object.
 */
final readonly class JobResult
{
    /**
     * @param bool $success Whether the job executed successfully
     * @param array<string, mixed>|null $output Job output data
     * @param string|null $error Error message if failed
     * @param bool $shouldRetry Whether to retry on failure
     * @param int|null $retryDelaySeconds Custom delay before retry (null = use default backoff)
     * @param DateTimeImmutable $executedAt When the job was executed
     * @param float $durationSeconds Execution time in seconds
     */
    public function __construct(
        public bool $success,
        public ?array $output = null,
        public ?string $error = null,
        public bool $shouldRetry = false,
        public ?int $retryDelaySeconds = null,
        public ?DateTimeImmutable $executedAt = null,
        public float $durationSeconds = 0.0,
    ) {
        if (!$success && $error === null) {
            throw new \InvalidArgumentException('Error message is required when success is false');
        }
        
        if ($success && $shouldRetry) {
            throw new \InvalidArgumentException('Cannot retry a successful job');
        }
        
        if ($retryDelaySeconds !== null && $retryDelaySeconds < 0) {
            throw new \InvalidArgumentException('Retry delay must be non-negative');
        }
        
        if ($durationSeconds < 0) {
            throw new \InvalidArgumentException('Duration must be non-negative');
        }
    }
    
    /**
     * Create a successful result
     *
     * @param array<string, mixed>|null $output
     */
    public static function success(?array $output = null): self
    {
        return new self(
            success: true,
            output: $output,
            executedAt: new DateTimeImmutable(),
        );
    }
    
    /**
     * Create a failure result
     *
     * @param string $error Error message
     * @param bool $shouldRetry Whether to retry
     * @param int|null $retryDelaySeconds Custom retry delay in seconds
     */
    public static function failure(
        string $error,
        bool $shouldRetry = false,
        ?int $retryDelaySeconds = null
    ): self {
        return new self(
            success: false,
            error: $error,
            shouldRetry: $shouldRetry,
            retryDelaySeconds: $retryDelaySeconds,
            executedAt: new DateTimeImmutable(),
        );
    }
    
    /**
     * Check if this is a retriable failure
     */
    public function isRetriable(): bool
    {
        return !$this->success && $this->shouldRetry;
    }
    
    /**
     * Check if this is a permanent failure
     */
    public function isPermanentFailure(): bool
    {
        return !$this->success && !$this->shouldRetry;
    }
    
    /**
     * Get the retry delay (custom or null for default backoff)
     */
    public function getRetryDelay(): ?int
    {
        return $this->isRetriable() ? $this->retryDelaySeconds : null;
    }
    
    /**
     * Create a new result with execution timing
     */
    public function withTiming(DateTimeImmutable $executedAt, float $durationSeconds): self
    {
        return new self(
            success: $this->success,
            output: $this->output,
            error: $this->error,
            shouldRetry: $this->shouldRetry,
            retryDelaySeconds: $this->retryDelaySeconds,
            executedAt: $executedAt,
            durationSeconds: $durationSeconds,
        );
    }
    
    /**
     * Convert to array for serialization
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'output' => $this->output,
            'error' => $this->error,
            'shouldRetry' => $this->shouldRetry,
            'retryDelaySeconds' => $this->retryDelaySeconds,
            'executedAt' => $this->executedAt?->format('c'),
            'durationSeconds' => $this->durationSeconds,
        ];
    }
    
    /**
     * Create from array
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success: $data['success'],
            output: $data['output'] ?? null,
            error: $data['error'] ?? null,
            shouldRetry: $data['shouldRetry'] ?? false,
            retryDelaySeconds: $data['retryDelaySeconds'] ?? null,
            executedAt: isset($data['executedAt']) ? new DateTimeImmutable($data['executedAt']) : null,
            durationSeconds: $data['durationSeconds'] ?? 0.0,
        );
    }
}
