<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

/**
 * Exception thrown when webhook payload parsing fails.
 */
class WebhookParsingException extends GatewayException
{
    public function __construct(
        string $message = 'Failed to parse webhook payload',
        ?string $rawPayload = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            gatewayErrorCode: 'webhook_parse_error',
            gatewayMessage: $message,
            previous: $previous,
        );
    }

    /**
     * Create for invalid JSON.
     */
    public static function invalidJson(string $error): self
    {
        return new self(
            message: "Invalid JSON in webhook payload: {$error}",
        );
    }

    /**
     * Create for missing required field.
     */
    public static function missingField(string $field): self
    {
        return new self(
            message: "Missing required field in webhook payload: {$field}",
        );
    }
}
