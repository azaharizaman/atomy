<?php

declare(strict_types=1);

namespace Nexus\Procurement\Events;

/**
 * Dispatched when a purchase order is amended.
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - Budget adjustment
 * - Vendor notification of amendment
 * - Re-approval workflow (if amount increased)
 * - Audit logging
 */
final readonly class PurchaseOrderAmendedEvent
{
    /**
     * @param string $purchaseOrderId Unique identifier of the PO
     * @param string $tenantId Tenant context
     * @param string $purchaseOrderNumber Human-readable PO number
     * @param string $amendedBy User ID who amended the PO
     * @param int $previousAmountCents Previous total amount in cents
     * @param int $newAmountCents New total amount in cents
     * @param int $amountChangeCents Change in amount (positive = increase, negative = decrease)
     * @param string $currency Currency code (ISO 4217)
     * @param string $amendmentReason Reason for amendment
     * @param int $revisionNumber Current revision number after amendment
     * @param array<string, mixed> $changedFields Fields that were changed
     * @param \DateTimeImmutable $amendedAt Timestamp of amendment
     */
    public function __construct(
        public string $purchaseOrderId,
        public string $tenantId,
        public string $purchaseOrderNumber,
        public string $amendedBy,
        public int $previousAmountCents,
        public int $newAmountCents,
        public int $amountChangeCents,
        public string $currency,
        public string $amendmentReason,
        public int $revisionNumber,
        public array $changedFields,
        public \DateTimeImmutable $amendedAt,
    ) {}
}
