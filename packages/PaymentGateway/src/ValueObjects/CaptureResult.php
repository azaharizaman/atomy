<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Enums\TransactionStatus;

/**
 * Result of a payment capture request.
 */
final class CaptureResult
{
    /**
     * @param bool $success Whether capture was successful
     * @param string|null $captureId Gateway capture ID
     * @param string|null $transactionId Gateway transaction reference
     * @param TransactionStatus $status Capture status
     * @param Money|null $capturedAmount Amount that was captured
     * @param Money|null $feeAmount Gateway/processing fee
     * @param Money|null $netAmount Net amount after fees
     * @param GatewayError|null $error Error details (if failed)
     * @param \DateTimeImmutable|null $capturedAt When capture occurred
     * @param array<string, mixed> $rawResponse Raw gateway response
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $captureId = null,
        public readonly ?string $transactionId = null,
        public readonly TransactionStatus $status = TransactionStatus::PENDING,
        public readonly ?Money $capturedAmount = null,
        public readonly ?Money $feeAmount = null,
        public readonly ?Money $netAmount = null,
        public readonly ?GatewayError $error = null,
        public readonly ?\DateTimeImmutable $capturedAt = null,
        public readonly array $rawResponse = [],
    ) {}

    /**
     * Create successful capture result.
     */
    public static function success(
        string $captureId,
        Money $amount,
        ?string $transactionId = null,
        ?Money $feeAmount = null,
        array $rawResponse = [],
    ): self {
        $netAmount = $feeAmount !== null
            ? $amount->subtract($feeAmount)
            : $amount;

        return new self(
            success: true,
            captureId: $captureId,
            transactionId: $transactionId,
            status: TransactionStatus::CAPTURED,
            capturedAmount: $amount,
            feeAmount: $feeAmount,
            netAmount: $netAmount,
            capturedAt: new \DateTimeImmutable(),
            rawResponse: $rawResponse,
        );
    }

    /**
     * Create failed capture result.
     */
    public static function failed(
        GatewayError $error,
        ?string $transactionId = null,
        array $rawResponse = [],
    ): self {
        return new self(
            success: false,
            transactionId: $transactionId,
            status: TransactionStatus::FAILED,
            error: $error,
            rawResponse: $rawResponse,
        );
    }

    /**
     * Check if capture can be refunded.
     */
    public function canRefund(): bool
    {
        return $this->success && $this->status->canRefund();
    }
}
