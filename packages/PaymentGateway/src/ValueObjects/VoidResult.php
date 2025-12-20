<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\ValueObjects;

use Nexus\PaymentGateway\Enums\TransactionStatus;

/**
 * Result of a void/cancel request.
 */
final class VoidResult
{
    /**
     * @param bool $success Whether void was successful
     * @param string|null $voidId Gateway void reference
     * @param string|null $transactionId Original transaction reference
     * @param TransactionStatus $status Void status
     * @param GatewayError|null $error Error details (if failed)
     * @param \DateTimeImmutable|null $voidedAt When void occurred
     * @param array<string, mixed> $rawResponse Raw gateway response
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $voidId = null,
        public readonly ?string $transactionId = null,
        public readonly TransactionStatus $status = TransactionStatus::PENDING,
        public readonly ?GatewayError $error = null,
        public readonly ?\DateTimeImmutable $voidedAt = null,
        public readonly array $rawResponse = [],
    ) {}

    /**
     * Create successful void result.
     */
    public static function success(
        string $voidId,
        ?string $transactionId = null,
        array $rawResponse = [],
    ): self {
        return new self(
            success: true,
            voidId: $voidId,
            transactionId: $transactionId,
            status: TransactionStatus::VOIDED,
            voidedAt: new \DateTimeImmutable(),
            rawResponse: $rawResponse,
        );
    }

    /**
     * Create failed void result.
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
}
