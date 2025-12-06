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
        public readonly string $requisitionId,
        public readonly string $tenantId,
        public readonly string $requisitionNumber,
        public readonly string $rejectedBy,
        public readonly string $rejectionReason,
        public readonly int $releasedAmountCents,
        public readonly string $currency,
        public readonly \DateTimeImmutable $rejectedAt,
    ) {}
}
