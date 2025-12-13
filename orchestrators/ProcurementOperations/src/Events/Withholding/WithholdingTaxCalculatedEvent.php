<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Withholding;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when withholding tax is calculated for a payment.
 */
final readonly class WithholdingTaxCalculatedEvent
{
    public function __construct(
        public string $paymentId,
        public string $vendorId,
        public Money $grossAmount,
        public Money $withholdingAmount,
        public Money $netAmount,
        public float $withholdingRate,
        public string $incomeType,
        public bool $isTreatyRate,
        public string $remittanceId,
        public string $certificateId,
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
            'gross_amount' => $this->grossAmount->getAmount(),
            'withholding_amount' => $this->withholdingAmount->getAmount(),
            'net_amount' => $this->netAmount->getAmount(),
            'currency' => $this->grossAmount->getCurrency(),
            'withholding_rate' => $this->withholdingRate,
            'income_type' => $this->incomeType,
            'is_treaty_rate' => $this->isTreatyRate,
            'remittance_id' => $this->remittanceId,
            'certificate_id' => $this->certificateId,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::RFC3339),
        ];
    }
}
