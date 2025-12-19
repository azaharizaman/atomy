<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use DateTimeImmutable;
use Nexus\Payment\Enums\SettlementBatchStatus;

/**
 * Settlement Batch Query Interface
 *
 * Defines read operations for settlement batches (CQRS Query side).
 *
 * @package Nexus\Payment\Contracts
 */
interface SettlementBatchQueryInterface
{
    /**
     * Find a settlement batch by ID.
     *
     * @param string $id Batch ID
     * @return SettlementBatchInterface|null
     */
    public function findById(string $id): ?SettlementBatchInterface;

    /**
     * Find batches by tenant.
     *
     * @param string $tenantId Tenant ID
     * @return array<SettlementBatchInterface>
     */
    public function findByTenant(string $tenantId): array;

    /**
     * Find batches by processor.
     *
     * @param string $tenantId Tenant ID
     * @param string $processorId Processor ID
     * @return array<SettlementBatchInterface>
     */
    public function findByProcessor(string $tenantId, string $processorId): array;

    /**
     * Find batches by status.
     *
     * @param string $tenantId Tenant ID
     * @param SettlementBatchStatus $status Batch status
     * @return array<SettlementBatchInterface>
     */
    public function findByStatus(string $tenantId, SettlementBatchStatus $status): array;

    /**
     * Find the currently open batch for a processor.
     *
     * @param string $tenantId Tenant ID
     * @param string $processorId Processor ID
     * @return SettlementBatchInterface|null
     */
    public function findOpenBatch(string $tenantId, string $processorId): ?SettlementBatchInterface;

    /**
     * Find batches that need reconciliation (closed but not reconciled).
     *
     * @param string $tenantId Tenant ID
     * @return array<SettlementBatchInterface>
     */
    public function findPendingReconciliation(string $tenantId): array;

    /**
     * Find batches within a date range.
     *
     * @param string $tenantId Tenant ID
     * @param DateTimeImmutable $from Start date
     * @param DateTimeImmutable $to End date
     * @return array<SettlementBatchInterface>
     */
    public function findByDateRange(
        string $tenantId,
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): array;

    /**
     * Find batch containing a specific payment.
     *
     * @param string $paymentId Payment ID
     * @return SettlementBatchInterface|null
     */
    public function findByPaymentId(string $paymentId): ?SettlementBatchInterface;

    /**
     * Find batches with discrepancies.
     *
     * @param string $tenantId Tenant ID
     * @return array<SettlementBatchInterface>
     */
    public function findDisputed(string $tenantId): array;
}
