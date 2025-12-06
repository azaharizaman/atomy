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
        public readonly string $requisitionId,
        public readonly string $tenantId,
        public readonly string $requisitionNumber,
        public readonly string $requestedBy,
        public readonly string $departmentId,
        public readonly array $lineItems,
        public readonly int $totalEstimatedAmountCents,
        public readonly string $currency,
        public readonly ?string $costCenterId,
        public readonly ?string $projectId,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}
