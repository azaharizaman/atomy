<?php

declare(strict_types=1);

namespace Nexus\Connector\ValueObjects;

/**
 * Immutable retry policy configuration.
 */
final readonly class RetryPolicy
{
    /**
     * @param int $maxAttempts Maximum number of retry attempts
     * @param int $initialDelayMs Initial delay in milliseconds
     * @param float $multiplier Exponential backoff multiplier
     * @param int $maxDelayMs Maximum delay between retries in milliseconds
     * @param array<int> $retryableStatusCodes HTTP status codes that should trigger retry
     */
    public function __construct(
        public int $maxAttempts = 3,
        public int $initialDelayMs = 1000,
        public float $multiplier = 2.0,
        public int $maxDelayMs = 30000,
        public array $retryableStatusCodes = [429, 500, 502, 503, 504],
    ) {}

    /**
     * Check if a status code is retryable.
     */
    public function isRetryable(int $statusCode): bool
    {
        return in_array($statusCode, $this->retryableStatusCodes, true);
    }

    /**
     * Calculate delay for a given attempt number.
     */
    public function calculateDelay(int $attempt): int
    {
        if ($attempt <= 0) {
            return 0;
        }

        $delay = $this->initialDelayMs * ($this->multiplier ** ($attempt - 1));
        
        return (int) min($delay, $this->maxDelayMs);
    }

    /**
     * Create a policy with no retries.
     */
    public static function noRetry(): self
    {
        return new self(maxAttempts: 1);
    }

    /**
     * Create a policy with aggressive retries.
     */
    public static function aggressive(): self
    {
        return new self(
            maxAttempts: 5,
            initialDelayMs: 500,
            multiplier: 1.5
        );
    }
}
