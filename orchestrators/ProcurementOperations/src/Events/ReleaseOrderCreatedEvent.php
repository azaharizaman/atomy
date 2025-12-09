<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when a Release Order is created against a Blanket PO.
 */
final readonly class ReleaseOrderCreatedEvent
{
    /**
     * @param string $releaseOrderId Release order identifier
     * @param string $releaseOrderNumber Human-readable release order number
     * @param string $blanketPoId Parent blanket PO identifier
     * @param string $tenantId Tenant identifier
     * @param int $amountCents Release order amount in cents
     * @param string $currency Currency code
     * @param int $newCumulativeSpendCents Updated cumulative spend on blanket PO
     * @param int $remainingBudgetCents Remaining budget on blanket PO
     * @param string $createdBy User who created the release order
     * @param \DateTimeImmutable $occurredAt When the event occurred
     */
    public function __construct(
        public string $releaseOrderId,
        public string $releaseOrderNumber,
        public string $blanketPoId,
        public string $tenantId,
        public int $amountCents,
        public string $currency,
        public int $newCumulativeSpendCents,
        public int $remainingBudgetCents,
        public string $createdBy,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    /**
     * Get the event name for dispatch.
     */
    public function getEventName(): string
    {
        return 'procurement.release_order.created';
    }

    /**
     * Get event payload for serialization.
     */
    public function toArray(): array
    {
        return [
            'release_order_id' => $this->releaseOrderId,
            'release_order_number' => $this->releaseOrderNumber,
            'blanket_po_id' => $this->blanketPoId,
            'tenant_id' => $this->tenantId,
            'amount_cents' => $this->amountCents,
            'currency' => $this->currency,
            'new_cumulative_spend_cents' => $this->newCumulativeSpendCents,
            'remaining_budget_cents' => $this->remainingBudgetCents,
            'created_by' => $this->createdBy,
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
