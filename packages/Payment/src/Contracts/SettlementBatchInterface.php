<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\SettlementBatchStatus;

/**
 * Settlement Batch Interface
 *
 * Represents a grouping of payments for processor settlement/reconciliation.
 * Settlement batches are used to match payment processor statements with
 * internal payment records.
 *
 * @package Nexus\Payment\Contracts
 */
interface SettlementBatchInterface
{
    /**
     * Get the unique batch identifier.
     */
    public function getId(): string;

    /**
     * Get the tenant identifier.
     */
    public function getTenantId(): string;

    /**
     * Get the payment processor/gateway identifier.
     * Examples: "stripe", "paypal", "adyen", "ach_processor"
     */
    public function getProcessorId(): string;

    /**
     * Get the batch reference number from the processor.
     * This is typically provided by the payment processor in their settlement report.
     */
    public function getProcessorBatchReference(): ?string;

    /**
     * Get the current batch status.
     */
    public function getStatus(): SettlementBatchStatus;

    /**
     * Get the batch opening date/time.
     */
    public function getOpenedAt(): DateTimeImmutable;

    /**
     * Get the batch closing date/time.
     */
    public function getClosedAt(): ?DateTimeImmutable;

    /**
     * Get the settlement date (when funds were/will be transferred).
     */
    public function getSettlementDate(): ?DateTimeImmutable;

    /**
     * Get all payment IDs in this batch.
     *
     * @return array<string>
     */
    public function getPaymentIds(): array;

    /**
     * Get the count of payments in this batch.
     */
    public function getPaymentCount(): int;

    /**
     * Get the total gross amount (before fees).
     */
    public function getGrossAmount(): Money;

    /**
     * Get the total processor fees.
     */
    public function getTotalFees(): Money;

    /**
     * Get the total net amount (after fees).
     */
    public function getNetAmount(): Money;

    /**
     * Get the expected settlement amount from processor.
     * Used for reconciliation comparison.
     */
    public function getExpectedSettlementAmount(): ?Money;

    /**
     * Get the actual settlement amount from processor statement.
     */
    public function getActualSettlementAmount(): ?Money;

    /**
     * Get the discrepancy amount (difference between expected and actual).
     * Returns null if not yet reconciled.
     */
    public function getDiscrepancyAmount(): ?Money;

    /**
     * Get optional batch metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Get batch creation timestamp.
     */
    public function getCreatedAt(): DateTimeImmutable;

    /**
     * Get batch last update timestamp.
     * Returns null if batch has never been updated after creation.
     */
    public function getUpdatedAt(): ?DateTimeImmutable;

    /**
     * Check if batch is open for new payments.
     */
    public function isOpen(): bool;

    /**
     * Check if batch has been reconciled.
     */
    public function isReconciled(): bool;

    /**
     * Check if batch has discrepancies.
     */
    public function hasDiscrepancy(): bool;

    // ========== Mutation Methods ==========

    /**
     * Add a payment to this batch.
     *
     * @param string $paymentId Payment ID to add
     * @param Money $amount Payment amount
     * @param Money $fee Processor fee for this payment
     * @throws \Nexus\Payment\Exceptions\InvalidSettlementBatchStatusException If batch is not open
     */
    public function addPayment(string $paymentId, Money $amount, Money $fee): void;

    /**
     * Remove a payment from this batch.
     *
     * @param string $paymentId Payment ID to remove
     * @param Money $amount Payment amount to subtract
     * @param Money $fee Processor fee to subtract
     * @throws \Nexus\Payment\Exceptions\InvalidSettlementBatchStatusException If batch is not open
     */
    public function removePayment(string $paymentId, Money $amount, Money $fee): void;

    /**
     * Close the batch (no more payments can be added).
     *
     * @throws \Nexus\Payment\Exceptions\InvalidSettlementBatchStatusException If batch cannot be closed
     */
    public function close(): void;

    /**
     * Set the expected settlement amount from processor.
     *
     * @param Money $amount Expected settlement amount
     */
    public function setExpectedSettlement(Money $amount): void;

    /**
     * Reconcile the batch with actual settlement.
     *
     * @param Money $actualAmount Actual amount received from processor
     * @param string|null $reference Settlement reference from processor
     * @throws \Nexus\Payment\Exceptions\InvalidSettlementBatchStatusException If batch cannot be reconciled
     */
    public function reconcile(Money $actualAmount, ?string $reference = null): void;

    /**
     * Mark batch as disputed due to discrepancies.
     *
     * @param string $reason Reason for dispute
     * @throws \Nexus\Payment\Exceptions\InvalidSettlementBatchStatusException If batch cannot be disputed
     */
    public function markDisputed(string $reason): void;

    /**
     * Set the processor batch reference.
     *
     * @param string $reference Processor's batch reference number
     */
    public function setProcessorBatchReference(string $reference): void;

    /**
     * Set the settlement date.
     *
     * @param DateTimeImmutable $date Expected or actual settlement date
     */
    public function setSettlementDate(DateTimeImmutable $date): void;

    /**
     * Update batch metadata.
     *
     * @param array<string, mixed> $metadata Metadata to merge
     */
    public function setMetadata(array $metadata): void;
}
