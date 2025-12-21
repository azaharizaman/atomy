<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Events;

use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\ValueObjects\VoidResult;

/**
 * Event dispatched when a payment authorization is voided.
 */
final readonly class PaymentVoidedEvent
{
    public function __construct(
        public string $tenantId,
        public string $voidId,
        public string $authorizationId,
        public string $transactionReference,
        public GatewayProvider $provider,
        public VoidResult $result,
        public \DateTimeImmutable $occurredAt,
    ) {}

    /**
     * Create from void result.
     */
    public static function fromResult(
        string $tenantId,
        string $authorizationId,
        string $transactionReference,
        GatewayProvider $provider,
        VoidResult $result,
    ): self {
        return new self(
            tenantId: $tenantId,
            voidId: $result->voidId ?? '',
            authorizationId: $authorizationId,
            transactionReference: $transactionReference,
            provider: $provider,
            result: $result,
            occurredAt: new \DateTimeImmutable(),
        );
    }
}
