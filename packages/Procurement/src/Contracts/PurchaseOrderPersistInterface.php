<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Interface for persisting purchase order changes.
 */
interface PurchaseOrderPersistInterface
{
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
     * @param string $tenantId
     * @param string $blanketPoId
     * @param string $creatorId
     * @param array<string, mixed> $data
     * @return PurchaseOrderInterface
     */
    public function createRelease(string $tenantId, string $blanketPoId, string $creatorId, array $data): PurchaseOrderInterface;

    /**
     * Approve purchase order.
     *
     * @param string $poId
     * @param string $approverId
     * @param string $tenantId
     * @return PurchaseOrderInterface
     */
    public function approve(string $poId, string $approverId, string $tenantId): PurchaseOrderInterface;

    /**
     * Update PO status.
     *
     * @param string $poId
     * @param string $status
     * @param string $tenantId
     * @return PurchaseOrderInterface
     */
    public function updateStatus(string $poId, string $status, string $tenantId): PurchaseOrderInterface;
}
