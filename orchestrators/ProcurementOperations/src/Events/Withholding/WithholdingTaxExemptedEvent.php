<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Withholding;

/**
 * Event dispatched when a payment is exempt from withholding tax.
 */
final readonly class WithholdingTaxExemptedEvent
{
    public function __construct(
        public string $paymentId,
        public string $vendorId,
        public string $exemptionReason,
        public ?string $exemptionCertificateId = null,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'payment_id' => $this->paymentId,
            'vendor_id' => $this->vendorId,
            'exemption_reason' => $this->exemptionReason,
            'exemption_certificate_id' => $this->exemptionCertificateId,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::RFC3339),
        ];
    }
}
