<?php

declare(strict_types=1);

namespace Nexus\Connector\Exceptions;

/**
 * Exception thrown when rate limit is exceeded.
 */
class RateLimitException extends ConnectorException
{
    public function __construct(
        string $message,
        public readonly string $serviceName,
        public readonly ?int $retryAfterSeconds = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function exceeded(string $serviceName, ?int $retryAfterSeconds = null): self
    {
        $message = "Rate limit exceeded for {$serviceName}";
        
        if ($retryAfterSeconds !== null) {
            $message .= ". Retry after {$retryAfterSeconds} seconds";
        }

        return new self(
            message: $message,
            serviceName: $serviceName,
            retryAfterSeconds: $retryAfterSeconds
        );
    }
}
