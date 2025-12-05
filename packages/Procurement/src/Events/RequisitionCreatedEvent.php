<?php

declare(strict_types=1);

namespace Nexus\Procurement\Events;

/**
 * Dispatched when a purchase requisition is created.
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - Budget commitment
 * - Routing for approval workflow
 * - Notifications to approvers
 */
final readonly class RequisitionCreatedEvent
{
    /**
     * @param string $requisitionId Unique identifier of the requisition
     * @param string $tenantId Tenant context
     * @param string $requisitionNumber Human-readable requisition number
     * @param string $requestedBy User ID of the requester
     * @param string $departmentId Department requesting the items
     * @param array<int, array{
     *     lineId: string,
     *     productId: string,
     *     description: string,
     *     quantity: float,
     *     unitOfMeasure: string,
     *     estimatedUnitPrice: int,
     *     currency: string,
     *     requestedDeliveryDate: string|null
     * }> $lineItems Requisition line items
     * @param int $totalEstimatedAmountCents Total estimated amount in cents
     * @param string $currency Currency code (ISO 4217)
     * @param string|null $costCenterId Optional cost center
     * @param string|null $projectId Optional project reference
     * @param \DateTimeImmutable $createdAt Timestamp of creation
     */
    public function __construct(
        public string $requisitionId,
        public string $tenantId,
        public string $requisitionNumber,
        public string $requestedBy,
        public string $departmentId,
        public array $lineItems,
        public int $totalEstimatedAmountCents,
        public string $currency,
        public ?string $costCenterId,
        public ?string $projectId,
        public \DateTimeImmutable $createdAt,
    ) {}
}
