<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for creating a purchase requisition.
 */
final readonly class CreateRequisitionRequest
{
    /**
     * @param string $tenantId Tenant context
     * @param string $requestedBy User ID creating the requisition
     * @param string $departmentId Requesting department
     * @param array<int, array{
     *     productId: string,
     *     description: string,
     *     quantity: float,
     *     estimatedUnitPriceCents: int,
     *     uom: string,
     *     preferredVendorId?: string|null,
     *     accountCode?: string|null,
     *     costCenterId?: string|null,
     *     deliveryDate?: string|null
     * }> $lineItems Requisition line items
     * @param string|null $justification Business justification
     * @param string|null $urgency Priority level (low, normal, high, urgent)
     * @param \DateTimeImmutable|null $requiredByDate Required delivery date
     * @param string|null $budgetId Budget to commit against
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public string $requestedBy,
        public string $departmentId,
        public array $lineItems,
        public ?string $justification = null,
        public ?string $urgency = 'normal',
        public ?\DateTimeImmutable $requiredByDate = null,
        public ?string $budgetId = null,
        public array $metadata = [],
    ) {}
}
