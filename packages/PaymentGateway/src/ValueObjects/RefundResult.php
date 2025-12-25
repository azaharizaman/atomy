<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentGateway\Enums\RefundType;
use Nexus\PaymentGateway\Enums\TransactionStatus;

/**
 * Result of a refund request.
 */
final class RefundResult
{
    /**
     * @param bool $success Whether refund was successful
     * @param string|null $refundId Gateway refund ID
     * @param string|null $transactionId Original transaction reference
     * @param TransactionStatus $status Refund status
     * @param RefundType $type Type of refund (full/partial)
     * @param Money|null $refundedAmount Amount refunded
     * @param GatewayError|null $error Error details (if failed)
     * @param string|null $reason Refund reason
     * @param \DateTimeImmutable|null $refundedAt When refund occurred
     * @param array<string, mixed> $rawResponse Raw gateway response
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $refundId = null,
        public readonly ?string $transactionId = null,
        public readonly TransactionStatus $status = TransactionStatus::PENDING,
        public readonly RefundType $type = RefundType::FULL,
        public readonly ?Money $refundedAmount = null,
        public readonly ?GatewayError $error = null,
        public readonly ?string $reason = null,
        public readonly ?\DateTimeImmutable $refundedAt = null,
        public readonly array $rawResponse = [],
    ) {}

    /**
     * Create successful refund result.
     */
    public static function success(
        string $refundId,
        Money $amount,
        RefundType $type,
        ?string $transactionId = null,
        ?string $reason = null,
        array $rawResponse = [],
    ): self {
        return new self(
            success: true,
            refundId: $refundId,
            transactionId: $transactionId,
            status: TransactionStatus::REFUNDED,
            type: $type,
            refundedAmount: $amount,
            reason: $reason,
            refundedAt: new \DateTimeImmutable(),
            rawResponse: $rawResponse,
        );
    }

    /**
     * Create failed refund result.
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
     * Check if this is a full refund.
     */
    public function isFullRefund(): bool
    {
        return $this->type === RefundType::FULL;
    }

    /**
     * Check if this is a partial refund.
     */
    public function isPartialRefund(): bool
    {
        return $this->type === RefundType::PARTIAL;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'refundId' => $this->refundId,
            'transactionId' => $this->transactionId,
            'status' => $this->status->value,
            'type' => $this->type->value,
            'refundedAmount' => $this->refundedAmount?->toArray(),
            'error' => $this->error?->toArray(),
            'reason' => $this->reason,
            'refundedAt' => $this->refundedAt?->format(\DateTimeInterface::ATOM),
            'rawResponse' => $this->rawResponse,
        ];
    }

    /**
     * Create from array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success: $data['success'],
            refundId: $data['refundId'] ?? null,
            transactionId: $data['transactionId'] ?? null,
            status: TransactionStatus::from($data['status']),
            type: RefundType::from($data['type']),
            refundedAmount: isset($data['refundedAmount']) ? Money::fromArray($data['refundedAmount']) : null,
            error: isset($data['error']) ? GatewayError::fromArray($data['error']) : null,
            reason: $data['reason'] ?? null,
            refundedAt: isset($data['refundedAt']) ? new \DateTimeImmutable($data['refundedAt']) : null,
            rawResponse: $data['rawResponse'] ?? [],
        );
    }
}
