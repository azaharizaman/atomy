<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Events;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\ValueObjects\AuthorizationResult;

/**
 * Event dispatched when a payment is successfully authorized.
 */
final readonly class PaymentAuthorizedEvent
{
    public function __construct(
        public string $tenantId,
        public string $authorizationId,
        public string $transactionReference,
        public GatewayProvider $provider,
        public Money $amount,
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
            authorizationId: $result->authorizationId ?? '',
            transactionReference: $transactionReference,
            provider: $provider,
            amount: $amount,
            result: $result,
            occurredAt: new \DateTimeImmutable(),
        );
    }
}
