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

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'voidId' => $this->voidId,
            'transactionId' => $this->transactionId,
            'status' => $this->status->value,
            'error' => $this->error?->toArray(),
            'voidedAt' => $this->voidedAt?->format(\DateTimeInterface::ATOM),
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
            success: (bool) ($data['success'] ?? false),
            voidId: $data['voidId'] ?? $data['void_id'] ?? null,
            transactionId: $data['transactionId'] ?? $data['transaction_id'] ?? null,
            status: TransactionStatus::tryFrom($data['status'] ?? '') ?? TransactionStatus::PENDING,
            error: isset($data['error']) ? GatewayError::fromArray($data['error']) : null,
            voidedAt: isset($data['voidedAt']) ? new \DateTimeImmutable($data['voidedAt']) : (isset($data['voided_at']) ? new \DateTimeImmutable($data['voided_at']) : null),
            rawResponse: $data['rawResponse'] ?? $data['raw_response'] ?? [],
        );
    }
}
