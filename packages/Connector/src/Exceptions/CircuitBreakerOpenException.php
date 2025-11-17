<?php

declare(strict_types=1);

namespace Nexus\Connector\Exceptions;

/**
 * Exception thrown when circuit breaker is open.
 */
class CircuitBreakerOpenException extends ConnectorException
{
    public function __construct(
        string $message,
        public readonly string $serviceName,
        public readonly int $secondsUntilRetry,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function open(string $serviceName, int $secondsUntilRetry): self
    {
        return new self(
            message: "Circuit breaker is open for {$serviceName}. Retry in {$secondsUntilRetry} seconds",
            serviceName: $serviceName,
            secondsUntilRetry: $secondsUntilRetry
        );
    }
}
