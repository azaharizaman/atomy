<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Purchase order repository interface.
 *
 * This interface provides methods for both internal procurement operations
 * and external 3-way matching requirements from Nexus\Payable.
 */
interface PurchaseOrderRepositoryInterface
{
    /**
     * Find purchase order by ID.
     *
     * @param string $id PO ULID
     * @return PurchaseOrderInterface|null
     */
    public function findById(string $id): ?PurchaseOrderInterface;

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
     * Required by Nexus\Payable for 3-way matching.
     *
     * @param string $lineReference PO line reference (e.g., "PO-2024-001-L1")
     * @return PurchaseOrderLineInterface|null
     */
    public function findLineByReference(string $lineReference): ?PurchaseOrderLineInterface;

    /**
     * Save purchase order.
     *
     * @param PurchaseOrderInterface $purchaseOrder
     * @return void
     */
    public function save(PurchaseOrderInterface $purchaseOrder): void;

    /**
     * Generate next PO number.
     *
     * @param string $tenantId Tenant ULID
     * @return string Next PO number
     */
    public function generateNextNumber(string $tenantId): string;

    /**
     * Create purchase order from requisition.
     *
     * @param string $tenantId
     * @param string $requisitionId
     * @param string $creatorId
     * @param array<string, mixed> $data
     * @return PurchaseOrderInterface
     */
    public function create(string $tenantId, string $requisitionId, string $creatorId, array $data): PurchaseOrderInterface;

    /**
     * Create blanket PO.
     *
     * @param string $tenantId
     * @param string $creatorId
     * @param array<string, mixed> $data
     * @return PurchaseOrderInterface
     */
    public function createBlanket(string $tenantId, string $creatorId, array $data): PurchaseOrderInterface;

    /**
     * Create release against blanket PO.
     *
     * @param string $blanketPoId
     * @param string $creatorId
     * @param array<string, mixed> $data
     * @return PurchaseOrderInterface
     */
    public function createRelease(string $blanketPoId, string $creatorId, array $data): PurchaseOrderInterface;

    /**
     * Approve purchase order.
     *
     * @param string $poId
     * @param string $approverId
     * @return PurchaseOrderInterface
     */
    public function approve(string $poId, string $approverId): PurchaseOrderInterface;

    /**
     * Update PO status.
     *
     * @param string $poId
     * @param string $status
     * @return PurchaseOrderInterface
     */
    public function updateStatus(string $poId, string $status): PurchaseOrderInterface;

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
