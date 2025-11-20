<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Repository interface for payment schedule persistence operations.
 */
interface PaymentScheduleRepositoryInterface
{
    /**
     * Find payment schedule by ID.
     *
     * @param string $id Payment schedule ULID
     * @return PaymentScheduleInterface|null
     */
    public function findById(string $id): ?PaymentScheduleInterface;

    /**
     * Find payment schedule by bill ID.
     *
     * @param string $billId Bill ULID
     * @return PaymentScheduleInterface|null
     */
    public function findByBillId(string $billId): ?PaymentScheduleInterface;

    /**
     * Get payment schedules for a vendor.
     *
     * @param string $tenantId Tenant ULID
     * @param string $vendorId Vendor ULID
     * @param array $filters Optional filters (status, date_range)
     * @return array<PaymentScheduleInterface>
     */
    public function getByVendor(string $tenantId, string $vendorId, array $filters = []): array;

    /**
     * Get payment schedules due by a specific date.
     *
     * @param string $tenantId Tenant ULID
     * @param \DateTimeInterface $asOfDate Due date cutoff
     * @return array<PaymentScheduleInterface>
     */
    public function getDueByDate(string $tenantId, \DateTimeInterface $asOfDate): array;

    /**
     * Get overdue payment schedules.
     *
     * @param string $tenantId Tenant ULID
     * @param \DateTimeInterface $asOfDate Current date
     * @return array<PaymentScheduleInterface>
     */
    public function getOverdue(string $tenantId, \DateTimeInterface $asOfDate): array;

    /**
     * Create a new payment schedule.
     *
     * @param string $tenantId Tenant ULID
     * @param array $data Payment schedule data
     * @return PaymentScheduleInterface
     */
    public function create(string $tenantId, array $data): PaymentScheduleInterface;

    /**
     * Update payment schedule.
     *
     * @param string $id Payment schedule ULID
     * @param array $data Updated payment schedule data
     * @return PaymentScheduleInterface
     */
    public function update(string $id, array $data): PaymentScheduleInterface;

    /**
     * Delete payment schedule.
     *
     * @param string $id Payment schedule ULID
     * @return bool True if deleted, false otherwise
     */
    public function delete(string $id): bool;
}
