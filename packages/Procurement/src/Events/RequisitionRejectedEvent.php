<?php

declare(strict_types=1);

namespace Nexus\Procurement\Events;

/**
 * Dispatched when a purchase requisition is rejected.
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - Release budget commitment
 * - Notification to requester
 * - Audit logging
 */
final readonly class RequisitionRejectedEvent
{
    /**
     * @param string $requisitionId Unique identifier of the requisition
     * @param string $tenantId Tenant context
     * @param string $requisitionNumber Human-readable requisition number
     * @param string $rejectedBy User ID of the rejector
     * @param string $rejectionReason Reason for rejection
     * @param int $releasedAmountCents Amount to release from budget commitment
     * @param string $currency Currency code (ISO 4217)
     * @param \DateTimeImmutable $rejectedAt Timestamp of rejection
     */
    public function __construct(
        private string $requisitionId,
        private string $tenantId,
        private string $requisitionNumber,
        private string $rejectedBy,
        private string $rejectionReason,
        private int $releasedAmountCents,
        private string $currency,
        private \DateTimeImmutable $rejectedAt,
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

    public function getRejectedBy(): string
    {
        return $this->rejectedBy;
    }

    public function getRejectionReason(): string
    {
        return $this->rejectionReason;
    }

    public function getReleasedAmountCents(): int
    {
        return $this->releasedAmountCents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getRejectedAt(): \DateTimeImmutable
    {
        return $this->rejectedAt;
    }
}
