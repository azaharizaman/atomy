<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for three-way invoice matching.
 */
final readonly class MatchInvoiceRequest
{
    /**
     * @param string $tenantId Tenant context
     * @param string $vendorBillId Vendor bill/invoice to match
     * @param string $purchaseOrderId Purchase order to match against
     * @param array<string> $goodsReceiptIds Goods receipts to match against
     * @param string $performedBy User ID performing the match
     * @param bool $allowVariance Whether to allow matching with variance (requires approval)
     * @param string|null $varianceApprovalReason Reason for approving variance (if allowVariance=true)
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public string $vendorBillId,
        public string $purchaseOrderId,
        public array $goodsReceiptIds,
        public string $performedBy,
        public bool $allowVariance = false,
        public ?string $varianceApprovalReason = null,
        public array $metadata = [],
    ) {}
}
