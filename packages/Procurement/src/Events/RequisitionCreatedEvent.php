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
        private string $requisitionId,
        private string $tenantId,
        private string $requisitionNumber,
        private string $requestedBy,
        private string $departmentId,
        private array $lineItems,
        private int $totalEstimatedAmountCents,
        private string $currency,
        private ?string $costCenterId,
        private ?string $projectId,
        private \DateTimeImmutable $createdAt,
    ) {}

    public function getRequisitionId(): string
    {
        return $this->requisitionId;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getRequisitionNumber(): string
    {
        return $this->requisitionNumber;
    }

    public function getRequestedBy(): string
    {
        return $this->requestedBy;
    }

    public function getDepartmentId(): string
    {
        return $this->departmentId;
    }

    /**
     * @return array<int, array{
     *     lineId: string,
     *     productId: string,
     *     description: string,
     *     quantity: float,
     *     unitOfMeasure: string,
     *     estimatedUnitPrice: int,
     *     currency: string,
     *     requestedDeliveryDate: string|null
     * }>
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function getTotalEstimatedAmountCents(): int
    {
        return $this->totalEstimatedAmountCents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCostCenterId(): ?string
    {
        return $this->costCenterId;
    }

    public function getProjectId(): ?string
    {
        return $this->projectId;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
