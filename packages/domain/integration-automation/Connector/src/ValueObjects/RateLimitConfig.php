<?php

declare(strict_types=1);

namespace Nexus\Connector\ValueObjects;

/**
 * Immutable rate limit configuration.
 */
final readonly class RateLimitConfig
{
    /**
     * @param int $maxRequests Maximum requests allowed in the time window
     * @param int $windowSeconds Time window in seconds
     */
    public function __construct(
        public int $maxRequests,
        public int $windowSeconds,
    ) {}

    /**
     * Create rate limit for requests per second.
     */
    public static function perSecond(int $maxRequests): self
    {
        return new self(maxRequests: $maxRequests, windowSeconds: 1);
    }

    /**
     * Create rate limit for requests per minute.
     */
    public static function perMinute(int $maxRequests): self
    {
        return new self(maxRequests: $maxRequests, windowSeconds: 60);
    }

    /**
     * Create rate limit for requests per hour.
     */
    public static function perHour(int $maxRequests): self
    {
        return new self(maxRequests: $maxRequests, windowSeconds: 3600);
    }

    /**
     * Calculate tokens to add per second.
     */
    public function tokensPerSecond(): float
    {
        return $this->maxRequests / $this->windowSeconds;
    }
}
