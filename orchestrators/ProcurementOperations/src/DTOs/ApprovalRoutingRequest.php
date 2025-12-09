<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for approval routing determination.
 *
 * Contains all information needed to determine the appropriate
 * approval chain for a requisition or purchase order.
 */
final readonly class ApprovalRoutingRequest
{
    /**
     * @param string $tenantId Tenant context
     * @param string $documentId Document ID (requisition or PO)
     * @param string $documentType Document type ('requisition' or 'purchase_order')
     * @param int $amountCents Total amount in cents
     * @param string $currency Currency code
     * @param string $requesterId User ID of requester
     * @param string $departmentId Department ID
     * @param string|null $costCenterId Cost center ID if applicable
     * @param string|null $categoryCode Purchase category code
     * @param array<string, mixed> $metadata Additional metadata for routing decisions
     */
    public function __construct(
        public string $tenantId,
        public string $documentId,
        public string $documentType,
        public int $amountCents,
        public string $currency,
        public string $requesterId,
        public string $departmentId,
        public ?string $costCenterId = null,
        public ?string $categoryCode = null,
        public array $metadata = [],
    ) {}

    /**
     * Get the amount in decimal format.
     */
    public function getAmountDecimal(): float
    {
        return $this->amountCents / 100;
    }

    /**
     * Check if this is a requisition.
     */
    public function isRequisition(): bool
    {
        return $this->documentType === 'requisition';
    }

    /**
     * Check if this is a purchase order.
     */
    public function isPurchaseOrder(): bool
    {
        return $this->documentType === 'purchase_order';
    }
}
