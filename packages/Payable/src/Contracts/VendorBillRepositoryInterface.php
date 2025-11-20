<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Repository interface for vendor bill persistence operations.
 */
interface VendorBillRepositoryInterface
{
    /**
     * Find bill by ID.
     *
     * @param string $id Bill ULID
     * @return VendorBillInterface|null
     */
    public function findById(string $id): ?VendorBillInterface;

    /**
     * Find bill by bill number.
     *
     * @param string $tenantId Tenant ULID
     * @param string $billNumber Vendor bill number
     * @return VendorBillInterface|null
     */
    public function findByBillNumber(string $tenantId, string $billNumber): ?VendorBillInterface;

    /**
     * Get bills for a vendor.
     *
     * @param string $vendorId Vendor ULID
     * @param array $filters Optional filters (status, date_range)
     * @return array<VendorBillInterface>
     */
    public function getByVendor(string $vendorId, array $filters = []): array;

    /**
     * Get bills by status.
     *
     * @param string $tenantId Tenant ULID
     * @param string $status Bill status
     * @return array<VendorBillInterface>
     */
    public function getByStatus(string $tenantId, string $status): array;

    /**
     * Get bills pending matching.
     *
     * @param string $tenantId Tenant ULID
     * @return array<VendorBillInterface>
     */
    public function getPendingMatching(string $tenantId): array;

    /**
     * Get bills ready for GL posting.
     *
     * @param string $tenantId Tenant ULID
     * @return array<VendorBillInterface>
     */
    public function getReadyForPosting(string $tenantId): array;

    /**
     * Save bill (create or update).
     *
     * @param VendorBillInterface $bill Bill entity
     * @return VendorBillInterface
     */
    public function save(VendorBillInterface $bill): VendorBillInterface;

    /**
     * Delete bill.
     *
     * @param string $id Bill ULID
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Get bills for vendor aging report.
     *
     * @param string $tenantId Tenant ULID
     * @param \DateTimeInterface $asOfDate As-of date
     * @return array<VendorBillInterface>
     */
    public function getForAgingReport(string $tenantId, \DateTimeInterface $asOfDate): array;
}
