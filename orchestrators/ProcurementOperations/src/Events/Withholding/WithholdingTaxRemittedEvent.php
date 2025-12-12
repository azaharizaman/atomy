<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\Withholding;

use Nexus\Common\ValueObjects\Money;

/**
 * Event dispatched when withholding tax is remitted to tax authority.
 */
final readonly class WithholdingTaxRemittedEvent
{
    public function __construct(
        public string $remittanceId,
        public string $tenantId,
        public Money $amount,
        public string $incomeType,
        public string $countryCode,
        public \DateTimeImmutable $remittanceDate,
        public ?string $referenceNumber = null,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'remittance_id' => $this->remittanceId,
            'tenant_id' => $this->tenantId,
            'amount' => $this->amount->getAmount(),
            'currency' => $this->amount->getCurrency(),
            'income_type' => $this->incomeType,
            'country_code' => $this->countryCode,
            'remittance_date' => $this->remittanceDate->format('Y-m-d'),
            'reference_number' => $this->referenceNumber,
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::RFC3339),
        ];
    }
}
