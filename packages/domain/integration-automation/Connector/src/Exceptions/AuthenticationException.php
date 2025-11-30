<?php

declare(strict_types=1);

namespace Nexus\Connector\Exceptions;

/**
 * Exception thrown when authentication fails.
 */
class AuthenticationException extends ConnectorException
{
    public function __construct(
        string $message,
        public readonly string $serviceName,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function invalidCredentials(string $serviceName): self
    {
        return new self(
            message: "Invalid credentials for {$serviceName}",
            serviceName: $serviceName
        );
    }

    public static function tokenExpired(string $serviceName): self
    {
        return new self(
            message: "Authentication token expired for {$serviceName}",
            serviceName: $serviceName
        );
    }

    public static function refreshFailed(string $serviceName, \Throwable $previous): self
    {
        return new self(
            message: "Failed to refresh credentials for {$serviceName}: {$previous->getMessage()}",
            serviceName: $serviceName,
            previous: $previous
        );
    }
}
