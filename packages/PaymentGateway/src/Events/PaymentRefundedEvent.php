<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Events;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\Enums\RefundType;
use Nexus\PaymentGateway\ValueObjects\RefundResult;

/**
 * Event dispatched when a payment is successfully refunded.
 */
final readonly class PaymentRefundedEvent
{
    public function __construct(
        public string $tenantId,
        public string $refundId,
        public string $captureId,
        public string $transactionReference,
        public GatewayProvider $provider,
        public Money $refundedAmount,
        public RefundType $refundType,
        public ?string $reason,
        public RefundResult $result,
        public \DateTimeImmutable $occurredAt,
    ) {}

    /**
     * Create from refund result.
     */
    public static function fromResult(
        string $tenantId,
        string $captureId,
        string $transactionReference,
        GatewayProvider $provider,
        RefundResult $result,
        ?string $reason = null,
    ): self {
        if ($result->refundedAmount === null) {
            throw new \InvalidArgumentException('RefundResult must have a refunded amount to create event');
        }

        return new self(
            tenantId: $tenantId,
            refundId: $result->refundId ?? '',
            captureId: $captureId,
            transactionReference: $transactionReference,
            provider: $provider,
            refundedAmount: $result->refundedAmount,
            refundType: $result->type,
            reason: $reason,
            result: $result,
            occurredAt: new \DateTimeImmutable(),
        );
    }
}
