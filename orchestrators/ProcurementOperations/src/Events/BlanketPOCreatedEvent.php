<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when a Blanket Purchase Order is created.
 */
final readonly class BlanketPOCreatedEvent
{
    /**
     * @param string $blanketPoId Blanket PO identifier
     * @param string $blanketPoNumber Human-readable blanket PO number
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Associated vendor
     * @param int $maxAmountCents Maximum spend limit
     * @param string $currency Currency code
     * @param \DateTimeImmutable $effectiveFrom Agreement start date
     * @param \DateTimeImmutable $effectiveTo Agreement end date
     * @param string $createdBy User who created the blanket PO
     * @param \DateTimeImmutable $occurredAt When the event occurred
     */
    public function __construct(
        public string $blanketPoId,
        public string $blanketPoNumber,
        public string $tenantId,
        public string $vendorId,
        public int $maxAmountCents,
        public string $currency,
        public \DateTimeImmutable $effectiveFrom,
        public \DateTimeImmutable $effectiveTo,
        public string $createdBy,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    /**
     * Get the event name for dispatch.
     */
    public function getEventName(): string
    {
        return 'procurement.blanket_po.created';
    }

    /**
     * Get event payload for serialization.
     */
    public function toArray(): array
    {
        return [
            'blanket_po_id' => $this->blanketPoId,
            'blanket_po_number' => $this->blanketPoNumber,
            'tenant_id' => $this->tenantId,
            'vendor_id' => $this->vendorId,
            'max_amount_cents' => $this->maxAmountCents,
            'currency' => $this->currency,
            'effective_from' => $this->effectiveFrom->format('c'),
            'effective_to' => $this->effectiveTo->format('c'),
            'created_by' => $this->createdBy,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
