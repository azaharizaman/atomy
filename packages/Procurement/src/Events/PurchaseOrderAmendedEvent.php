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
        private string $purchaseOrderId,
        private string $tenantId,
        private string $purchaseOrderNumber,
        private string $amendedBy,
        private int $previousAmountCents,
        private int $newAmountCents,
        private int $amountChangeCents,
        private string $currency,
        private string $amendmentReason,
        private int $revisionNumber,
        private array $changedFields,
        private \DateTimeImmutable $amendedAt,
    ) {}

    public function getPurchaseOrderId(): string
    {
        return $this->purchaseOrderId;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getPurchaseOrderNumber(): string
    {
        return $this->purchaseOrderNumber;
    }

    public function getAmendedBy(): string
    {
        return $this->amendedBy;
    }

    public function getPreviousAmountCents(): int
    {
        return $this->previousAmountCents;
    }

    public function getNewAmountCents(): int
    {
        return $this->newAmountCents;
    }

    public function getAmountChangeCents(): int
    {
        return $this->amountChangeCents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getAmendmentReason(): string
    {
        return $this->amendmentReason;
    }

    public function getRevisionNumber(): int
    {
        return $this->revisionNumber;
    }

    /**
     * @return array<string, mixed>
     */
    public function getChangedFields(): array
    {
        return $this->changedFields;
    }

    public function getAmendedAt(): \DateTimeImmutable
    {
        return $this->amendedAt;
    }
}
