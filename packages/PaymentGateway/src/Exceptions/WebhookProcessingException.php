<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

/**
 * Exception thrown when webhook processing fails.
 */
class WebhookProcessingException extends GatewayException
{
    public function __construct(
        string $message = 'Failed to process webhook',
        ?string $eventType = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            gatewayErrorCode: 'webhook_processing_failed',
            gatewayMessage: $eventType ? "Failed to process {$eventType} event" : $message,
            previous: $previous,
        );
    }

    /**
     * Create for unsupported event type.
     */
    public static function unsupportedEvent(string $eventType): self
    {
        return new self(
            message: "Unsupported webhook event type: {$eventType}",
            eventType: $eventType,
        );
    }

    /**
     * Create for duplicate event.
     */
    public static function duplicateEvent(string $eventId): self
    {
        return new self(
            message: "Webhook event already processed: {$eventId}",
        );
    }
}
