<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

/**
 * Receivable Schedule Repository Interface
 *
 * Defines data persistence operations for receivable schedules.
 */
interface ReceivableScheduleRepositoryInterface
{
    public function findById(string $id): ?ReceivableScheduleInterface;

    public function save(ReceivableScheduleInterface $schedule): void;

    public function delete(string $id): void;

    /**
     * Get all schedules for an invoice
     *
     * @return ReceivableScheduleInterface[]
     */
    public function getByInvoice(string $invoiceId): array;

    /**
     * Get pending (unpaid) schedules for a customer
     *
     * @return ReceivableScheduleInterface[]
     */
    public function getPendingSchedules(string $tenantId, string $customerId): array;

    /**
     * Get overdue schedules
     *
     * @return ReceivableScheduleInterface[]
     */
    public function getOverdueSchedules(string $tenantId, \DateTimeInterface $asOfDate): array;
}
