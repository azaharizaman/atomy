<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Events;

use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\ValueObjects\GatewayError;

/**
 * Event dispatched when a gateway error occurs.
 */
final readonly class GatewayErrorEvent
{
    public function __construct(
        public string $tenantId,
        public GatewayProvider $provider,
        public string $operation,
        public GatewayError $error,
        public ?string $transactionReference,
        public \DateTimeImmutable $occurredAt,
    ) {}

    /**
     * Create from error.
     */
    public static function fromError(
        string $tenantId,
        GatewayProvider $provider,
        string $operation,
        GatewayError $error,
        ?string $transactionReference = null,
    ): self {
        return new self(
            tenantId: $tenantId,
            provider: $provider,
            operation: $operation,
            error: $error,
            transactionReference: $transactionReference,
            occurredAt: new \DateTimeImmutable(),
        );
    }
}
