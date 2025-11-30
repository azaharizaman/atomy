<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Goods receipt note repository interface.
 *
 * Provides methods for both internal procurement operations
 * and external 3-way matching requirements from Nexus\Payable.
 */
interface GoodsReceiptRepositoryInterface
{
    /**
     * Find GRN by ID.
     *
     * @param string $id GRN ULID
     * @return GoodsReceiptNoteInterface|null
     */
    public function findById(string $id): ?GoodsReceiptNoteInterface;

    /**
     * Find GRN by number.
     *
     * @param string $tenantId Tenant ULID
     * @param string $grnNumber GRN number
     * @return GoodsReceiptNoteInterface|null
     */
    public function findByNumber(string $tenantId, string $grnNumber): ?GoodsReceiptNoteInterface;

    /**
     * Find GRN line by reference.
     *
     * Required by Nexus\Payable for 3-way matching.
     *
     * @param string $lineReference GRN line reference (e.g., "GRN-2024-001-L1")
     * @return GoodsReceiptLineInterface|null
     */
    public function findLineByReference(string $lineReference): ?GoodsReceiptLineInterface;

    /**
     * Find all GRNs for a purchase order.
     *
     * @param string $purchaseOrderId PO ULID
     * @return array<GoodsReceiptNoteInterface>
     */
    public function findByPurchaseOrder(string $purchaseOrderId): array;

    /**
     * Save GRN.
     *
     * @param GoodsReceiptNoteInterface $grn
     * @return void
     */
    public function save(GoodsReceiptNoteInterface $grn): void;

    /**
     * Generate next GRN number.
     *
     * @param string $tenantId Tenant ULID
     * @return string Next GRN number
     */
    public function generateNextNumber(string $tenantId): string;
}
