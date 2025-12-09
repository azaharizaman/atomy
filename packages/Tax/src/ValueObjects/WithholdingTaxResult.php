<?php

declare(strict_types=1);

namespace Nexus\Tax\ValueObjects;

use Nexus\Common\ValueObjects\Money;

/**
 * Withholding Tax Result: Complete result of WHT calculation
 * 
 * Contains:
 * - Gross amount (before WHT)
 * - Net amount (after WHT)
 * - WHT amount to withhold
 * - Breakdown of WHT by type (federal, state, etc.)
 * - Rate applied
 * - Form requirements (1099, 1042-S, etc.)
 */
final readonly class WithholdingTaxResult
{
    /**
     * @param Money $grossAmount Original payment amount
     * @param Money $netAmount Amount payable after WHT
     * @param Money $withholdingAmount Total WHT amount
     * @param float $effectiveRate Effective WHT rate applied (decimal)
     * @param string $jurisdictionCode Jurisdiction code applied
     * @param string|null $formType Required tax form (1099, 1042-S, etc.)
     * @param string|null $formCategory Category within form (1099-NEC, 1099-MISC, etc.)
     * @param array<WithholdingTaxLine> $lines Breakdown by tax type
     * @param array<string, mixed> $metadata Additional result metadata
     */
    public function __construct(
        public Money $grossAmount,
        public Money $netAmount,
        public Money $withholdingAmount,
        public float $effectiveRate,
        public string $jurisdictionCode,
        public ?string $formType = null,
        public ?string $formCategory = null,
        public array $lines = [],
        public array $metadata = [],
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->effectiveRate < 0 || $this->effectiveRate > 1) {
            throw new \InvalidArgumentException("Effective rate must be between 0 and 1: {$this->effectiveRate}");
        }

        // Net amount should equal gross minus withholding
        $expectedNet = $this->grossAmount->subtract($this->withholdingAmount);
        if (!$this->netAmount->equals($expectedNet)) {
            throw new \InvalidArgumentException('Net amount must equal gross minus withholding');
        }
    }

    /**
     * Check if any WHT was applied
     */
    public function hasWithholding(): bool
    {
        return $this->withholdingAmount->isPositive();
    }

    /**
     * Get WHT rate as percentage string
     */
    public function getRateAsPercentage(): string
    {
        return number_format($this->effectiveRate * 100, 2) . '%';
    }

    /**
     * Create zero-WHT result (no withholding applies)
     */
    public static function noWithholding(Money $grossAmount, string $jurisdictionCode): self
    {
        return new self(
            grossAmount: $grossAmount,
            netAmount: $grossAmount,
            withholdingAmount: Money::zero($grossAmount->getCurrency()),
            effectiveRate: 0.0,
            jurisdictionCode: $jurisdictionCode,
        );
    }
}
