<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for recording a service receipt.
 */
final readonly class ServiceReceiptRequest
{
    /**
     * @param string $tenantId Tenant context
     * @param string $purchaseOrderId Associated purchase order ID
     * @param array<int, array{
     *     poLineId: string,
     *     amountCents: int,
     *     description: string,
     *     serviceDate: string
     * }> $lineItems Service line items to accept
     * @param string $recordedBy User ID recording the receipt
     * @param string|null $approvalReference Reference to external approval/deliverable acceptance
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public string $purchaseOrderId,
        public array $lineItems,
        public string $recordedBy,
        public ?string $approvalReference = null,
        public array $metadata = []
    ) {}
}
