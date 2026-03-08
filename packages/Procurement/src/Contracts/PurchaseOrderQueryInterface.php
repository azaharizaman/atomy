<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Interface for reading purchase order data.
 */
interface PurchaseOrderQueryInterface
{
    /**
     * Find purchase order by ID.
     *
     * @param string $tenantId Tenant ULID
     * @param string $id PO ULID
     * @return PurchaseOrderInterface|null
     */
    public function findById(string $tenantId, string $id): ?PurchaseOrderInterface;

    /**
     * Find purchase order by PO number.
     *
     * @param string $tenantId Tenant ULID
     * @param string $poNumber PO number
     * @return PurchaseOrderInterface|null
     */
    public function findByNumber(string $tenantId, string $poNumber): ?PurchaseOrderInterface;

    /**
     * Find purchase order line by reference.
     *
     * @param string $tenantId Tenant ULID
     * @param string $lineReference PO line reference
     * @return PurchaseOrderLineInterface|null
     */
    public function findLineByReference(string $tenantId, string $lineReference): ?PurchaseOrderLineInterface;

    /**
     * Find POs by tenant.
     *
     * @param string $tenantId
     * @param array<string, mixed> $filters
     * @return array<PurchaseOrderInterface>
     */
    public function findByTenantId(string $tenantId, array $filters): array;

    /**
     * Find POs by vendor.
     *
     * @param string $tenantId
     * @param string $vendorId
     * @return array<PurchaseOrderInterface>
     */
    public function findByVendorId(string $tenantId, string $vendorId): array;
}
