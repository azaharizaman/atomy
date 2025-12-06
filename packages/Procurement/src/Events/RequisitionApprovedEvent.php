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
        public readonly string $requisitionId,
        public readonly string $tenantId,
        public readonly string $requisitionNumber,
        public readonly string $approvedBy,
        public readonly ?string $approvalComments,
        public readonly int $approvedAmountCents,
        public readonly string $currency,
        public readonly \DateTimeImmutable $approvedAt,
    ) {}
}
