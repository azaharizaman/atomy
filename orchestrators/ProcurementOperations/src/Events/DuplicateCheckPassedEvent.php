<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when duplicate check passes (no duplicates found).
 */
final readonly class DuplicateCheckPassedEvent
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param string $invoiceNumber Invoice number that was checked
     * @param string $fingerprint Invoice fingerprint hash
     * @param \DateTimeImmutable $checkedAt When the check was performed
     */
    public function __construct(
        public string $tenantId,
        public string $vendorId,
        public string $invoiceNumber,
        public string $fingerprint,
        public \DateTimeImmutable $checkedAt = new \DateTimeImmutable(),
    ) {}

    /**
     * Get event name for dispatching.
     */
    public static function getEventName(): string
    {
        return 'procurement.invoice.duplicate_check_passed';
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
            'fingerprint' => $this->fingerprint,
            'checked_at' => $this->checkedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
