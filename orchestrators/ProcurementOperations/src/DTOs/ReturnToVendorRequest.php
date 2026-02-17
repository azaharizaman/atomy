<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for initiating a return to vendor.
 */
final readonly class ReturnToVendorRequest
{
    /**
     * @param string $tenantId Tenant context
     * @param string $goodsReceiptId Source goods receipt ID
     * @param array<int, array{
     *     goodsReceiptLineId: string,
     *     quantity: float,
     *     reason: string
     * }> $lineItems Items to return
     * @param string $initiatedBy User ID initiating the return
     * @param string|null $notes Additional notes
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public string $goodsReceiptId,
        public array $lineItems,
        public string $initiatedBy,
        public ?string $notes = null,
        public array $metadata = []
    ) {}
}
