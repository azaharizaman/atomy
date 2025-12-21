<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Exceptions;

use Nexus\PaymentGateway\Enums\GatewayProvider;

/**
 * Exception thrown when webhook verification fails.
 */
class WebhookVerificationFailedException extends GatewayException
{
    public function __construct(
        string $message,
        public readonly ?GatewayProvider $provider = null,
        public readonly ?string $reason = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            gatewayErrorCode: 'webhook_verification_failed',
            gatewayMessage: null,
            previous: $previous,
        );
    }

    /**
     * Create for invalid signature.
     */
    public static function invalidSignature(GatewayProvider $provider): self
    {
        return new self(
            message: 'Webhook signature verification failed',
            provider: $provider,
            reason: 'invalid_signature',
        );
    }

    /**
     * Create for expired timestamp.
     */
    public static function expiredTimestamp(GatewayProvider $provider): self
    {
        return new self(
            message: 'Webhook timestamp has expired',
            provider: $provider,
            reason: 'expired_timestamp',
        );
    }

    /**
     * Create for missing signature.
     */
    public static function missingSignature(GatewayProvider $provider): self
    {
        return new self(
            message: 'Webhook signature header is missing',
            provider: $provider,
            reason: 'missing_signature',
        );
    }
}
