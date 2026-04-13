<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Query interface for vendor bill retrieval operations.
 */
interface VendorBillQueryInterface
{
    /**
     * Find bill by tenant and ID.
     *
     * @param string $tenantId Tenant ULID
     * @param string $id Bill ULID
     * @return VendorBillInterface|null
     */
    public function findByTenantAndId(string $tenantId, string $id): ?VendorBillInterface;

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
     * @param string $tenantId Tenant ULID
     * @param string $vendorId Vendor ULID
     * @param array $filters Optional filters (status, date_range)
     * @return array<VendorBillInterface>
     */
    public function getByVendor(string $tenantId, string $vendorId, array $filters = []): array;

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
     * Get bills for vendor aging report.
     *
     * @param string $tenantId Tenant ULID
     * @param \DateTimeInterface $asOfDate As-of date
     * @return array<VendorBillInterface>
     */
    public function getForAgingReport(string $tenantId, \DateTimeInterface $asOfDate): array;
}
