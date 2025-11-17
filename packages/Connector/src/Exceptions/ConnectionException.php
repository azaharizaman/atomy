<?php

declare(strict_types=1);

namespace Nexus\Connector\Exceptions;

/**
 * Exception thrown when connection to external service fails.
 */
class ConnectionException extends ConnectorException
{
    public function __construct(
        string $message,
        public readonly string $serviceName,
        public readonly ?string $endpoint = null,
        public readonly ?int $httpStatusCode = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function timeout(string $serviceName, string $endpoint, int $timeoutSeconds): self
    {
        return new self(
            message: "Connection to {$serviceName} timed out after {$timeoutSeconds} seconds",
            serviceName: $serviceName,
            endpoint: $endpoint
        );
    }

    public static function networkError(string $serviceName, string $endpoint, \Throwable $previous): self
    {
        return new self(
            message: "Network error connecting to {$serviceName}: {$previous->getMessage()}",
            serviceName: $serviceName,
            endpoint: $endpoint,
            previous: $previous
        );
    }

    public static function httpError(string $serviceName, string $endpoint, int $statusCode, string $body): self
    {
        return new self(
            message: "HTTP {$statusCode} error from {$serviceName}: {$body}",
            serviceName: $serviceName,
            endpoint: $endpoint,
            httpStatusCode: $statusCode
        );
    }
}
