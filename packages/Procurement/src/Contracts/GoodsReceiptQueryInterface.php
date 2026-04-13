<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Goods receipt query interface.
 *
 * Provides tenant-scoped read signatures used by Layer 2 orchestrators.
 */
interface GoodsReceiptQueryInterface
{
    /**
     * Find GRN by ID and tenant.
     *
     * @param string $tenantId Tenant ULID
     * @param string $id GRN ULID
     * @return GoodsReceiptNoteInterface|null
     */
    public function findByTenantAndId(string $tenantId, string $id): ?GoodsReceiptNoteInterface;

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
     * @param string $tenantId Tenant ULID
     * @param string $lineReference GRN line reference (e.g., "GRN-2024-001-L1")
     * @return GoodsReceiptLineInterface|null
     */
    public function findLineByReference(string $tenantId, string $lineReference): ?GoodsReceiptLineInterface;

    /**
     * Find all GRNs for a purchase order.
     *
     * @param string $tenantId Tenant ULID
     * @param string $purchaseOrderId PO ULID
     * @return array<GoodsReceiptNoteInterface>
     */
    public function findByPurchaseOrder(string $tenantId, string $purchaseOrderId): array;

    /**
     * Find all GRNs for tenant.
     *
     * @param string $tenantId Tenant ULID
     * @param array<string, mixed> $filters
     * @return array<GoodsReceiptNoteInterface>
     */
    public function findByTenantId(string $tenantId, array $filters): array;

    /**
     * Generate next GRN number.
     *
     * @param string $tenantId Tenant ULID
     * @return string Next GRN number
     */
    public function generateNextNumber(string $tenantId): string;
}
