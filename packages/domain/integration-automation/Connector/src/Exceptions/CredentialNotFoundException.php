<?php

declare(strict_types=1);

namespace Nexus\Connector\Exceptions;

/**
 * Exception thrown when credentials are not found.
 */
class CredentialNotFoundException extends ConnectorException
{
    public function __construct(
        string $message,
        public readonly string $serviceName,
        public readonly ?string $tenantId = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function forService(string $serviceName, ?string $tenantId = null): self
    {
        $message = "Credentials not found for service: {$serviceName}";
        
        if ($tenantId !== null) {
            $message .= " (tenant: {$tenantId})";
        }

        return new self(
            message: $message,
            serviceName: $serviceName,
            tenantId: $tenantId
        );
    }
}
