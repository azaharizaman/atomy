<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Goods receipt persist interface.
 *
 * Provides mutation signatures used by Layer 2 orchestrators.
 */
interface GoodsReceiptPersistInterface
{
    /**
     * Create GRN.
     *
     * @param string $tenantId
     * @param string $purchaseOrderId
     * @param string $receiverId
     * @param array<string, mixed> $data
     * @return GoodsReceiptNoteInterface
     */
    public function create(string $tenantId, string $purchaseOrderId, string $receiverId, array $data): GoodsReceiptNoteInterface;

    /**
     * Authorize payment for goods receipt.
     *
     * @param string $tenantId
     * @param string $grnId
     * @param string $authorizerId
     * @return GoodsReceiptNoteInterface
     */
    public function authorizePayment(string $tenantId, string $grnId, string $authorizerId): GoodsReceiptNoteInterface;

    /**
     * Save GRN.
     *
     * @param string $tenantId Tenant ULID
     * @param GoodsReceiptNoteInterface $grn
     * @return void
     */
    public function save(string $tenantId, GoodsReceiptNoteInterface $grn): void;

    /**
     * Create a return to vendor record.
     *
     * @param string $tenantId Tenant ULID
     * @param string $goodsReceiptId GRN ULID
     * @param array<int, array{productId: string, quantity: float, reason?: string}> $lineItems
     * @param array<string, mixed> $data
     * @return string Return ID
     */
    public function createReturn(string $tenantId, string $goodsReceiptId, array $lineItems, array $data): string;

    /**
     * Update return status.
     *
     * @param string $tenantId Tenant ULID
     * @param string $returnId Return ULID
     * @param string $status
     * @param array<string, mixed> $data
     * @return void
     */
    public function updateReturnStatus(string $tenantId, string $returnId, string $status, array $data): void;
}
