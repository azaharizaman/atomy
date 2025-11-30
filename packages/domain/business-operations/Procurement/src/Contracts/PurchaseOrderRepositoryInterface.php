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
}
