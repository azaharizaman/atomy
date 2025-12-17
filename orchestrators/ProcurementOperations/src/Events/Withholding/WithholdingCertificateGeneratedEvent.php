<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Withholding;

/**
 * Event dispatched when a withholding certificate is generated.
 */
final readonly class WithholdingCertificateGeneratedEvent
{
    public function __construct(
        public string $certificateId,
        public string $paymentId,
        public string $vendorId,
        public string $tenantId,
        public float $withholdingRate,
        public string $incomeType,
        public bool $isTreatyRate,
        public ?string $treatyCountry = null,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'certificate_id' => $this->certificateId,
            'payment_id' => $this->paymentId,
            'vendor_id' => $this->vendorId,
            'tenant_id' => $this->tenantId,
            'withholding_rate' => $this->withholdingRate,
            'income_type' => $this->incomeType,
            'is_treaty_rate' => $this->isTreatyRate,
            'treaty_country' => $this->treatyCountry,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::RFC3339),
        ];
    }
}
