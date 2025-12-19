<?php

declare(strict_types=1);

namespace Nexus\Payment\Entities;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\PaymentTransactionInterface;
use Nexus\Payment\Enums\PaymentDirection;
use Nexus\Payment\Enums\PaymentMethodType;
use Nexus\Payment\Enums\PaymentStatus;
use Nexus\Payment\ValueObjects\ExecutionContext;
use Nexus\Payment\ValueObjects\IdempotencyKey;
use Nexus\Payment\ValueObjects\PaymentReference;

/**
 * Payment transaction entity.
 *
 * Represents a single payment transaction with full state tracking.
 */
final class PaymentTransaction implements PaymentTransactionInterface
{
    private PaymentStatus $status;

    private ?string $providerTransactionId = null;

    private ?Money $settledAmount = null;

    private ?\DateTimeImmutable $processedAt = null;

    private ?\DateTimeImmutable $completedAt = null;

    private ?string $failureCode = null;

    private ?string $failureMessage = null;

    private ?\DateTimeImmutable $cancelledAt = null;

    private ?string $description = null;

    private int $attemptCount = 0;

    /** @var array<string, mixed> */
    private array $metadata = [];

    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly PaymentReference $reference,
        private readonly PaymentDirection $direction,
        private readonly Money $amount,
        private readonly PaymentMethodType $methodType,
        private readonly ?string $payerId,
        private readonly ?string $payeeId,
        private readonly ?string $paymentMethodId,
        private readonly ?IdempotencyKey $idempotencyKey,
        private readonly ?ExecutionContext $executionContext,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->status = PaymentStatus::PENDING;
    }

    /**
     * Create a new payment transaction.
     */
    public static function create(
        string $id,
        string $tenantId,
        PaymentReference $reference,
        PaymentDirection $direction,
        Money $amount,
        PaymentMethodType $methodType,
        ?string $payerId = null,
        ?string $payeeId = null,
        ?string $paymentMethodId = null,
        ?IdempotencyKey $idempotencyKey = null,
        ?ExecutionContext $executionContext = null,
        ?string $description = null,
    ): self {
        $transaction = new self(
            id: $id,
            tenantId: $tenantId,
            reference: $reference,
            direction: $direction,
            amount: $amount,
            methodType: $methodType,
            payerId: $payerId,
            payeeId: $payeeId,
            paymentMethodId: $paymentMethodId,
            idempotencyKey: $idempotencyKey,
            executionContext: $executionContext,
            createdAt: new \DateTimeImmutable(),
        );
        $transaction->description = $description;

        return $transaction;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getReference(): PaymentReference
    {
        return $this->reference;
    }

    public function getDirection(): PaymentDirection
    {
        return $this->direction;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getMethodType(): PaymentMethodType
    {
        return $this->methodType;
    }

    public function getPayerId(): ?string
    {
        return $this->payerId;
    }

    public function getPayeeId(): ?string
    {
        return $this->payeeId;
    }

    public function getPaymentMethodId(): ?string
    {
        return $this->paymentMethodId;
    }

    public function getMethodId(): ?string
    {
        return $this->paymentMethodId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getInitiatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function getIdempotencyKey(): ?IdempotencyKey
    {
        return $this->idempotencyKey;
    }

    public function getExecutionContext(): ?ExecutionContext
    {
        return $this->executionContext;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getProviderTransactionId(): ?string
    {
        return $this->providerTransactionId;
    }

    public function getSettledAmount(): ?Money
    {
        return $this->settledAmount;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getFailureCode(): ?string
    {
        return $this->failureCode;
    }

    public function getFailureMessage(): ?string
    {
        return $this->failureMessage;
    }

    public function getAttemptCount(): int
    {
        return $this->attemptCount;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function canTransitionTo(PaymentStatus $status): bool
    {
        return $this->status->canTransitionTo($status);
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function canBeCancelled(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function canBeReversed(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    /**
     * Transition to processing status.
     */
    public function markAsProcessing(?string $providerTransactionId = null): void
    {
        $this->transitionTo(PaymentStatus::PROCESSING);
        $this->providerTransactionId = $providerTransactionId;
        $this->processedAt = new \DateTimeImmutable();
        $this->attemptCount++;
    }

    /**
     * Transition to completed status.
     */
    public function markAsCompleted(
        Money $settledAmount,
        ?string $providerTransactionId = null,
    ): void {
        $this->transitionTo(PaymentStatus::COMPLETED);
        $this->settledAmount = $settledAmount;
        $this->completedAt = new \DateTimeImmutable();

        if ($providerTransactionId !== null) {
            $this->providerTransactionId = $providerTransactionId;
        }
    }

    /**
     * Transition to failed status.
     */
    public function markAsFailed(string $failureCode, string $failureMessage): void
    {
        $this->transitionTo(PaymentStatus::FAILED);
        $this->failureCode = $failureCode;
        $this->failureMessage = $failureMessage;
    }

    /**
     * Transition to cancelled status.
     */
    public function markAsCancelled(?string $reason = null): void
    {
        $this->transitionTo(PaymentStatus::CANCELLED);
        $this->cancelledAt = new \DateTimeImmutable();

        if ($reason !== null) {
            $this->metadata['cancellation_reason'] = $reason;
        }
    }

    /**
     * Transition to reversed status.
     */
    public function markAsReversed(?string $reason = null, ?string $reversalTransactionId = null): void
    {
        $this->transitionTo(PaymentStatus::REVERSED);

        if ($reason !== null) {
            $this->metadata['reversal_reason'] = $reason;
        }

        if ($reversalTransactionId !== null) {
            $this->metadata['reversal_transaction_id'] = $reversalTransactionId;
        }
    }

    /**
     * Add metadata to the payment.
     *
     * @param array<string, mixed> $metadata
     */
    public function addMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
    }

    /**
     * Transition to a new status.
     */
    private function transitionTo(PaymentStatus $newStatus): void
    {
        if (!$this->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Cannot transition from {$this->status->value} to {$newStatus->value}"
            );
        }

        $this->status = $newStatus;
    }
}
