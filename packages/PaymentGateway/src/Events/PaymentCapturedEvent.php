<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Events;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Enums\GatewayProvider;
use Nexus\PaymentGateway\ValueObjects\CaptureResult;

/**
 * Event dispatched when a payment is successfully captured.
 */
final readonly class PaymentCapturedEvent
{
    public function __construct(
        public string $tenantId,
        public string $captureId,
        public string $authorizationId,
        public string $transactionReference,
        public GatewayProvider $provider,
        public Money $capturedAmount,
        public bool $finalCapture,
        public CaptureResult $result,
        public \DateTimeImmutable $occurredAt,
    ) {}

    /**
     * Create from capture result.
     */
    public static function fromResult(
        string $tenantId,
        string $authorizationId,
        string $transactionReference,
        GatewayProvider $provider,
        CaptureResult $result,
        bool $finalCapture = true,
    ): self {
        if ($result->capturedAmount === null) {
            throw new \InvalidArgumentException('CaptureResult must have a captured amount to create event');
        }

        return new self(
            tenantId: $tenantId,
            captureId: $result->captureId ?? '',
            authorizationId: $authorizationId,
            transactionReference: $transactionReference,
            provider: $provider,
            capturedAmount: $result->capturedAmount,
            finalCapture: $finalCapture,
            result: $result,
            occurredAt: new \DateTimeImmutable(),
        );
    }
}
