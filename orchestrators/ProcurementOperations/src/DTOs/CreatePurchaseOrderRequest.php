<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for creating a purchase order from a requisition.
 */
final readonly class CreatePurchaseOrderRequest
{
    /**
     * @param string $tenantId Tenant context
     * @param string $requisitionId Source requisition ID
     * @param string $vendorId Vendor to order from
     * @param string $createdBy User ID creating the PO
     * @param array<int, array{
     *     requisitionLineId: string,
     *     productId: string,
     *     description: string,
     *     quantity: float,
     *     unitPriceCents: int,
     *     uom: string,
     *     taxCode?: string|null,
     *     deliveryDate?: string|null
     * }>|null $lineItems Line items (null = use all from requisition)
     * @param string|null $paymentTerms Payment terms code
     * @param string|null $deliveryAddress Delivery address
     * @param string|null $contractId Associated contract (if any)
     * @param string|null $currency Currency code (default: tenant base currency)
     * @param string|null $notes Notes for vendor
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public string $requisitionId,
        public string $vendorId,
        public string $createdBy,
        public ?array $lineItems = null,
        public ?string $paymentTerms = null,
        public ?string $deliveryAddress = null,
        public ?string $contractId = null,
        public ?string $currency = null,
        public ?string $notes = null,
        public array $metadata = [],
    ) {}
}
