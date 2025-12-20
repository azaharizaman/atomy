<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Events;

use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\ValueObjects\PaymentToken;

/**
 * Event dispatched when a payment token is created.
 */
final readonly class TokenCreatedEvent
{
    public function __construct(
        public string $tenantId,
        public string $customerId,
        public GatewayProvider $provider,
        public PaymentToken $token,
        public \DateTimeImmutable $occurredAt,
    ) {}

    /**
     * Create from token.
     */
    public static function fromToken(
        string $tenantId,
        string $customerId,
        GatewayProvider $provider,
        PaymentToken $token,
    ): self {
        return new self(
            tenantId: $tenantId,
            customerId: $customerId,
            provider: $provider,
            token: $token,
            occurredAt: new \DateTimeImmutable(),
        );
    }
}
