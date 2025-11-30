<?php

declare(strict_types=1);

namespace Nexus\Connector\Services;

use Nexus\Connector\Contracts\RateLimiterStorageInterface;
use Nexus\Connector\Exceptions\RateLimitExceededException;
use Nexus\Connector\ValueObjects\RateLimitConfig;

/**
 * Token bucket rate limiter for external API requests.
 *
 * Implements the token bucket algorithm to enforce rate limits per service.
 * Tokens are refilled continuously based on the configured rate.
 *
 * STATELESS: All token bucket state is delegated to injected storage.
 * This ensures global rate limiting across all PHP-FPM workers and Laravel Octane.
 */
final readonly class RateLimiter
{
    public function __construct(
        private RateLimiterStorageInterface $storage
    ) {}

    /**
     * Check if a request can proceed and consume a token.
     *
     * @param string $serviceName Service identifier
     * @param RateLimitConfig $config Rate limit configuration
     * @throws RateLimitExceededException If rate limit is exceeded
     */
    public function checkAndConsume(string $serviceName, RateLimitConfig $config): void
    {
        // Refill tokens based on elapsed time
        $this->storage->refillTokens($serviceName, $config);

        // Get current token count
        $availableTokens = $this->storage->getTokens($serviceName, $config);

        if ($availableTokens < 1.0) {
            $waitSeconds = (int) ceil((1.0 - $availableTokens) / $config->tokensPerSecond());
            
            throw RateLimitExceededException::limitExceeded(
                serviceName: $serviceName,
                limit: $config->maxRequests,
                windowSeconds: $config->windowSeconds,
                retryAfterSeconds: $waitSeconds
            );
        }

        // Consume one token
        $this->storage->consumeTokens($serviceName, $config, 1.0);
    }

    /**
     * Check if a request can proceed without consuming a token.
     *
     * @param string $serviceName Service identifier
     * @param RateLimitConfig $config Rate limit configuration
     * @return bool True if request can proceed
     */
    public function canProceed(string $serviceName, RateLimitConfig $config): bool
    {
        $this->storage->refillTokens($serviceName, $config);
        
        return $this->storage->getTokens($serviceName, $config) >= 1.0;
    }

    /**
     * Get current number of available tokens.
     *
     * @param string $serviceName Service identifier
     * @param RateLimitConfig $config Rate limit configuration
     * @return float Number of available tokens
     */
    public function getAvailableTokens(string $serviceName, RateLimitConfig $config): float
    {
        $this->storage->refillTokens($serviceName, $config);
        
        return $this->storage->getTokens($serviceName, $config);
    }

    /**
     * Reset rate limit bucket for a service.
     *
     * @param string $serviceName Service identifier
     */
    public function reset(string $serviceName): void
    {
        $this->storage->reset($serviceName);
    }
}
