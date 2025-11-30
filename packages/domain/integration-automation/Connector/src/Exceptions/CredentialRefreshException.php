<?php

declare(strict_types=1);

namespace Nexus\Connector\Exceptions;

/**
 * Exception thrown when credential refresh fails.
 */
class CredentialRefreshException extends ConnectorException
{
    public function __construct(
        string $message,
        public readonly string $serviceName,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function failed(string $serviceName, \Throwable $previous): self
    {
        return new self(
            message: "Failed to refresh credentials for {$serviceName}: {$previous->getMessage()}",
            serviceName: $serviceName,
            previous: $previous
        );
    }
}
