<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when duplicate invoice(s) are detected.
 */
final readonly class DuplicateInvoiceDetectedEvent
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param string $invoiceNumber Invoice number being checked
     * @param Money $amount Invoice amount
     * @param \DateTimeImmutable $invoiceDate Invoice date
     * @param int $matchCount Number of potential duplicates found
     * @param bool $shouldBlock Whether processing should be blocked
     * @param string $highestRiskLevel Highest risk level among matches
     * @param array<array<string, mixed>> $matches Details of matched invoices
     * @param \DateTimeImmutable $detectedAt When detection occurred
     */
    public function __construct(
        public string $tenantId,
        public string $vendorId,
        public string $invoiceNumber,
        public Money $amount,
        public \DateTimeImmutable $invoiceDate,
        public int $matchCount,
        public bool $shouldBlock,
        public string $highestRiskLevel,
        public array $matches,
        public \DateTimeImmutable $detectedAt = new \DateTimeImmutable(),
    ) {}

    /**
     * Get event name for dispatching.
     */
    public static function getEventName(): string
    {
        return 'procurement.invoice.duplicate_detected';
    }

    /**
     * Convert to array for logging/serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'vendor_id' => $this->vendorId,
            'invoice_number' => $this->invoiceNumber,
            'amount' => $this->amount->getAmount(),
            'currency' => $this->amount->getCurrency(),
            'invoice_date' => $this->invoiceDate->format('Y-m-d'),
            'match_count' => $this->matchCount,
            'should_block' => $this->shouldBlock,
            'highest_risk_level' => $this->highestRiskLevel,
            'matches' => $this->matches,
            'detected_at' => $this->detectedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
