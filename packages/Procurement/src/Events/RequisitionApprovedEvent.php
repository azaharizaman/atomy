<?php

declare(strict_types=1);

namespace Nexus\Procurement\Events;

/**
 * Dispatched when a purchase requisition is approved.
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - Automatic PO creation (if configured)
 * - Notification to requester
 * - Audit logging
 */
final readonly class RequisitionApprovedEvent
{
    /**
     * @param string $requisitionId Unique identifier of the requisition
     * @param string $tenantId Tenant context
     * @param string $requisitionNumber Human-readable requisition number
     * @param string $approvedBy User ID of the approver
     * @param string|null $approvalComments Optional approval comments
     * @param int $approvedAmountCents Approved amount in cents
     * @param string $currency Currency code (ISO 4217)
     * @param \DateTimeImmutable $approvedAt Timestamp of approval
     */
    public function __construct(
        private string $requisitionId,
        private string $tenantId,
        private string $requisitionNumber,
        private string $approvedBy,
        private ?string $approvalComments,
        private int $approvedAmountCents,
        private string $currency,
        private \DateTimeImmutable $approvedAt,
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

    public function getApprovedBy(): string
    {
        return $this->approvedBy;
    }

    public function getApprovalComments(): ?string
    {
        return $this->approvalComments;
    }

    public function getApprovedAmountCents(): int
    {
        return $this->approvedAmountCents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getApprovedAt(): \DateTimeImmutable
    {
        return $this->approvedAt;
    }
}
