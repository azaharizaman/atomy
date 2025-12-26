<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Events;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;
use Nexus\PaymentGateway\ValueObjects\GatewayError;

/**
 * Event dispatched when a payment authorization fails.
 */
final readonly class PaymentFailedEvent
{
    public function __construct(
        public string $tenantId,
        public string $transactionReference,
        public GatewayProvider $provider,
        public Money $amount,
        public ?GatewayError $error,
        public AuthorizationResult $result,
        public \DateTimeImmutable $occurredAt,
    ) {}

    /**
     * Create from authorization result.
     */
    public static function fromResult(
        string $tenantId,
        string $transactionReference,
        GatewayProvider $provider,
        Money $amount,
        AuthorizationResult $result,
    ): self {
        return new self(
            tenantId: $tenantId,
            transactionReference: $transactionReference,
            provider: $provider,
            amount: $amount,
            error: $result->error,
            result: $result,
            occurredAt: new \DateTimeImmutable(),
        );
    }
}
