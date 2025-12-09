<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when a contract spend limit warning threshold is reached.
 */
final readonly class ContractSpendLimitWarningEvent
{
    /**
     * @param string $blanketPoId Blanket PO identifier
     * @param string $blanketPoNumber Human-readable blanket PO number
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Associated vendor
     * @param int $maxAmountCents Maximum contract limit
     * @param int $currentSpendCents Current cumulative spend
     * @param int $percentUtilized Current utilization percentage
     * @param int $warningThresholdPercent Configured warning threshold
     * @param \DateTimeImmutable $effectiveTo Contract expiry date
     * @param \DateTimeImmutable $occurredAt When the event occurred
     */
    public function __construct(
        public string $blanketPoId,
        public string $blanketPoNumber,
        public string $tenantId,
        public string $vendorId,
        public int $maxAmountCents,
        public int $currentSpendCents,
        public int $percentUtilized,
        public int $warningThresholdPercent,
        public \DateTimeImmutable $effectiveTo,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    /**
     * Get the event name for dispatch.
     */
    public function getEventName(): string
    {
        return 'procurement.contract.spend_limit_warning';
    }

    /**
     * Get remaining budget in cents.
     */
    public function getRemainingCents(): int
    {
        return max(0, $this->maxAmountCents - $this->currentSpendCents);
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
            'current_spend_cents' => $this->currentSpendCents,
            'remaining_cents' => $this->getRemainingCents(),
            'percent_utilized' => $this->percentUtilized,
            'warning_threshold_percent' => $this->warningThresholdPercent,
            'effective_to' => $this->effectiveTo->format('c'),
            'occurred_at' => $this->occurredAt->format('c'),
        ];
    }
}
