<?php

declare(strict_types=1);

namespace Nexus\Payment\Entities;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Contracts\SettlementBatchInterface;
use Nexus\Payment\Enums\SettlementBatchStatus;
use Nexus\Payment\Exceptions\InvalidSettlementBatchStatusException;

/**
 * Settlement Batch Entity
 *
 * Groups payment transactions for reconciliation with payment processors.
 * Supports tracking of expected vs actual settlement amounts.
 */
final class SettlementBatch implements SettlementBatchInterface
{
    private SettlementBatchStatus $status;

    private ?DateTimeImmutable $closedAt = null;

    private ?DateTimeImmutable $settlementDate = null;

    private ?string $processorBatchReference = null;

    /** @var array<string> */
    private array $paymentIds = [];

    private Money $grossAmount;

    private Money $totalFees;

    private Money $netAmount;

    private ?Money $expectedSettlementAmount = null;

    private ?Money $actualSettlementAmount = null;

    /** @var array<string, mixed> */
    private array $metadata = [];

    private ?DateTimeImmutable $updatedAt = null;

    /**
     * @param string $id Unique batch identifier
     * @param string $tenantId Tenant identifier
     * @param string $processorId Payment processor identifier
     * @param string $currency Currency code for all amounts
     * @param DateTimeImmutable $openedAt When batch was opened
     * @param DateTimeImmutable $createdAt When batch was created
     */
    public function __construct(
        private readonly string $id,
        private readonly string $tenantId,
        private readonly string $processorId,
        private readonly string $currency,
        private readonly DateTimeImmutable $openedAt,
        private readonly DateTimeImmutable $createdAt,
    ) {
        // Validate ISO 4217 currency code (3 uppercase letters)
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException(sprintf(
                'Currency code must be a valid ISO 4217 3-letter code, got: %s',
                $currency
            ));
        }

        $this->status = SettlementBatchStatus::OPEN;
        $this->grossAmount = Money::zero($currency);
        $this->totalFees = Money::zero($currency);
        $this->netAmount = Money::zero($currency);
    }

    /**
     * Create a new settlement batch.
     */
    public static function create(
        string $id,
        string $tenantId,
        string $processorId,
        string $currency,
        array $metadata = [],
    ): self {
        $now = new DateTimeImmutable();
        $batch = new self(
            id: $id,
            tenantId: $tenantId,
            processorId: $processorId,
            currency: $currency,
            openedAt: $now,
            createdAt: $now,
        );
        $batch->metadata = $metadata;

        return $batch;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getProcessorId(): string
    {
        return $this->processorId;
    }

    public function getProcessorBatchReference(): ?string
    {
        return $this->processorBatchReference;
    }

    public function getStatus(): SettlementBatchStatus
    {
        return $this->status;
    }

    public function getOpenedAt(): DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getSettlementDate(): ?DateTimeImmutable
    {
        return $this->settlementDate;
    }

    /**
     * @return array<string>
     */
    public function getPaymentIds(): array
    {
        return $this->paymentIds;
    }

    public function getPaymentCount(): int
    {
        return count($this->paymentIds);
    }

    public function getGrossAmount(): Money
    {
        return $this->grossAmount;
    }

    public function getTotalFees(): Money
    {
        return $this->totalFees;
    }

    public function getNetAmount(): Money
    {
        return $this->netAmount;
    }

    public function getExpectedSettlementAmount(): ?Money
    {
        return $this->expectedSettlementAmount;
    }

    public function getActualSettlementAmount(): ?Money
    {
        return $this->actualSettlementAmount;
    }

    public function getDiscrepancyAmount(): ?Money
    {
        if ($this->expectedSettlementAmount === null || $this->actualSettlementAmount === null) {
            return null;
        }

        return $this->actualSettlementAmount->subtract($this->expectedSettlementAmount);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function isReconciled(): bool
    {
        return $this->status === SettlementBatchStatus::RECONCILED;
    }

    public function hasDiscrepancy(): bool
    {
        $discrepancy = $this->getDiscrepancyAmount();

        if ($discrepancy === null) {
            return false;
        }

        return !$discrepancy->isZero();
    }

    public function addPayment(string $paymentId, Money $amount, Money $fee): void
    {
        $this->assertOpen();
        $this->assertSameCurrency($amount);
        $this->assertSameCurrency($fee);

        if (!in_array($paymentId, $this->paymentIds, true)) {
            $this->paymentIds[] = $paymentId;
            $this->grossAmount = $this->grossAmount->add($amount);
            $this->totalFees = $this->totalFees->add($fee);
            $this->recalculateNet();
            $this->touch();
        }
    }

    public function removePayment(string $paymentId, Money $amount, Money $fee): void
    {
        $this->assertOpen();
        $this->assertSameCurrency($amount);
        $this->assertSameCurrency($fee);

        $index = array_search($paymentId, $this->paymentIds, true);
        if ($index !== false) {
            unset($this->paymentIds[$index]);
            $this->paymentIds = array_values($this->paymentIds);
            $this->grossAmount = $this->grossAmount->subtract($amount);
            $this->totalFees = $this->totalFees->subtract($fee);
            $this->recalculateNet();
            $this->touch();
        }
    }

    public function close(): void
    {
        $this->transitionTo(SettlementBatchStatus::CLOSED);
        $this->closedAt = new DateTimeImmutable();
        $this->expectedSettlementAmount = $this->netAmount;
        $this->touch();
    }

    public function setExpectedSettlement(Money $amount): void
    {
        $this->assertSameCurrency($amount);
        $this->expectedSettlementAmount = $amount;
        $this->touch();
    }

    public function reconcile(Money $actualAmount, ?string $reference = null): void
    {
        $this->assertSameCurrency($actualAmount);
        $this->transitionTo(SettlementBatchStatus::RECONCILED);
        $this->actualSettlementAmount = $actualAmount;
        
        if ($reference !== null) {
            $this->processorBatchReference = $reference;
        }
        
        $this->touch();
    }

    public function markDisputed(string $reason): void
    {
        $this->transitionTo(SettlementBatchStatus::DISPUTED);
        $this->metadata['dispute_reason'] = $reason;
        $this->metadata['disputed_at'] = (new DateTimeImmutable())->format(DateTimeImmutable::ATOM);
        $this->touch();
    }

    public function setProcessorBatchReference(string $reference): void
    {
        $this->processorBatchReference = $reference;
        $this->touch();
    }

    public function setSettlementDate(DateTimeImmutable $date): void
    {
        $this->settlementDate = $date;
        $this->touch();
    }

    /**
     * Merge additional metadata into existing metadata.
     * Existing keys will be overwritten if present in the new metadata.
     *
     * @param array<string, mixed> $metadata Metadata to merge
     */
    public function mergeMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        $this->touch();
    }

    /**
     * Replace all metadata with new metadata.
     *
     * @param array<string, mixed> $metadata New metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
        $this->touch();
    }

    /**
     * Get the currency for this batch.
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Recalculate net amount from gross and fees.
     */
    private function recalculateNet(): void
    {
        $this->netAmount = $this->grossAmount->subtract($this->totalFees);
    }

    /**
     * Transition to a new status with validation.
     */
    private function transitionTo(SettlementBatchStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new InvalidSettlementBatchStatusException($this->status, $newStatus);
        }
        $this->status = $newStatus;
    }

    /**
     * Assert the batch is open.
     */
    private function assertOpen(): void
    {
        if (!$this->isOpen()) {
            throw new InvalidSettlementBatchStatusException(
                $this->status,
                SettlementBatchStatus::OPEN,
                'Cannot modify a closed settlement batch'
            );
        }
    }

    /**
     * Assert the amount has the same currency as the batch.
     */
    private function assertSameCurrency(Money $amount): void
    {
        if ($amount->getCurrency() !== $this->currency) {
            throw new \InvalidArgumentException(sprintf(
                'Currency mismatch: expected %s, got %s',
                $this->currency,
                $amount->getCurrency()
            ));
        }
    }

    /**
     * Update the updatedAt timestamp.
     */
    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
