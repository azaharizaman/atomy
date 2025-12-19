<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;

/**
 * Settlement Batch Manager Interface
 *
 * Provides high-level operations for managing settlement batches.
 *
 * @package Nexus\Payment\Contracts
 */
interface SettlementBatchManagerInterface
{
    /**
     * Create a new settlement batch for a processor.
     *
     * The batch currency should be specified in metadata or configured at the processor level.
     * If not specified, the implementation should use the processor's default settlement currency.
     *
     * @param string $tenantId Tenant ID
     * @param string $processorId Payment processor ID
     * @param array<string, mixed> $metadata Optional metadata (may include 'currency' key)
     * @return SettlementBatchInterface
     */
    public function createBatch(
        string $tenantId,
        string $processorId,
        array $metadata = []
    ): SettlementBatchInterface;

    /**
     * Get or create the current open batch for a processor.
     * If no open batch exists, creates a new one.
     *
     * The batch currency is determined by the processor's configuration.
     * For cross-currency settlements, use createBatch() with explicit currency in metadata.
     *
     * @param string $tenantId Tenant ID
     * @param string $processorId Payment processor ID
     * @return SettlementBatchInterface
     */
    public function getOrCreateOpenBatch(
        string $tenantId,
        string $processorId
    ): SettlementBatchInterface;

    /**
     * Add a payment to the current open batch.
     *
     * The payment amount and fee currencies must match the batch currency.
     * For cross-currency payments, convert amounts to the batch currency before calling.
     *
     * @param string $tenantId Tenant ID
     * @param string $processorId Payment processor ID
     * @param string $paymentId Payment ID
     * @param Money $amount Payment amount (must match batch currency)
     * @param Money $fee Processor fee (must match batch currency)
     * @return SettlementBatchInterface Updated batch
     * @throws \InvalidArgumentException If currency does not match batch currency
     */
    public function addPaymentToBatch(
        string $tenantId,
        string $processorId,
        string $paymentId,
        Money $amount,
        Money $fee
    ): SettlementBatchInterface;

    /**
     * Close the current open batch for a processor.
     *
     * @param string $tenantId Tenant ID
     * @param string $processorId Payment processor ID
     * @return SettlementBatchInterface Closed batch
     * @throws \Nexus\Payment\Exceptions\SettlementBatchNotFoundException If no open batch
     */
    public function closeBatch(string $tenantId, string $processorId): SettlementBatchInterface;

    /**
     * Close a specific batch by ID.
     *
     * @param string $batchId Batch ID
     * @return SettlementBatchInterface Closed batch
     * @throws \Nexus\Payment\Exceptions\SettlementBatchNotFoundException If not found
     */
    public function closeBatchById(string $batchId): SettlementBatchInterface;

    /**
     * Reconcile a batch with processor settlement data.
     *
     * @param string $batchId Batch ID
     * @param Money $actualAmount Actual settlement amount from processor
     * @param string|null $processorReference Processor's settlement reference
     * @param DateTimeImmutable|null $settlementDate Actual settlement date
     * @return SettlementBatchInterface Reconciled batch
     * @throws \Nexus\Payment\Exceptions\SettlementBatchNotFoundException If not found
     */
    public function reconcileBatch(
        string $batchId,
        Money $actualAmount,
        ?string $processorReference = null,
        ?DateTimeImmutable $settlementDate = null
    ): SettlementBatchInterface;

    /**
     * Mark a batch as disputed.
     *
     * @param string $batchId Batch ID
     * @param string $reason Reason for dispute
     * @return SettlementBatchInterface Disputed batch
     * @throws \Nexus\Payment\Exceptions\SettlementBatchNotFoundException If not found
     */
    public function disputeBatch(string $batchId, string $reason): SettlementBatchInterface;

    /**
     * Get a batch by ID.
     *
     * @param string $batchId Batch ID
     * @return SettlementBatchInterface
     * @throws \Nexus\Payment\Exceptions\SettlementBatchNotFoundException If not found
     */
    public function getBatch(string $batchId): SettlementBatchInterface;

    /**
     * Get all batches pending reconciliation.
     *
     * @param string $tenantId Tenant ID
     * @return array<SettlementBatchInterface>
     */
    public function getPendingReconciliation(string $tenantId): array;

    /**
     * Get all disputed batches.
     *
     * @param string $tenantId Tenant ID
     * @return array<SettlementBatchInterface>
     */
    public function getDisputedBatches(string $tenantId): array;

    /**
     * Recalculate batch totals from associated payments.
     *
     * Fetches all payments linked to this batch and recalculates:
     * - grossAmount: Sum of all payment amounts
     * - totalFees: Sum of all processor fees
     * - netAmount: grossAmount - totalFees
     *
     * Useful after payment modifications, reversals, or reconciliation adjustments.
     *
     * @param string $batchId Batch ID
     * @return SettlementBatchInterface Updated batch with recalculated totals
     * @throws \Nexus\Payment\Exceptions\SettlementBatchNotFoundException If batch not found
     */
    public function recalculateTotals(string $batchId): SettlementBatchInterface;
}
