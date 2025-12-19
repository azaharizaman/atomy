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
     * @param string $tenantId Tenant ID
     * @param string $processorId Payment processor ID
     * @param array<string, mixed> $metadata Optional metadata
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
     * @param string $tenantId Tenant ID
     * @param string $processorId Payment processor ID
     * @param string $paymentId Payment ID
     * @param Money $amount Payment amount
     * @param Money $fee Processor fee
     * @return SettlementBatchInterface Updated batch
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
     * Calculate batch totals from payments.
     * Useful for recalculating after payment modifications.
     *
     * @param string $batchId Batch ID
     * @return SettlementBatchInterface Updated batch
     */
    public function recalculateTotals(string $batchId): SettlementBatchInterface;
}
