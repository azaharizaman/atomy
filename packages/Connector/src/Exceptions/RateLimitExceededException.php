<?php

declare(strict_types=1);

namespace Nexus\Connector\Exceptions;

/**
 * Exception thrown when rate limit is exceeded.
 */
final class RateLimitExceededException extends \RuntimeException
{
    public function __construct(
        public readonly string $serviceName,
        public readonly int $limit,
        public readonly int $windowSeconds,
        public readonly int $retryAfterSeconds,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for rate limit exceeded.
     */
    public static function limitExceeded(
        string $serviceName,
        int $limit,
        int $windowSeconds,
        int $retryAfterSeconds
    ): self {
        return new self(
            serviceName: $serviceName,
            limit: $limit,
            windowSeconds: $windowSeconds,
            retryAfterSeconds: $retryAfterSeconds,
            message: sprintf(
                'Rate limit exceeded for service "%s". Limit: %d requests per %d seconds. Retry after %d seconds.',
                $serviceName,
                $limit,
                $windowSeconds,
                $retryAfterSeconds
            )
        );
    }
}
