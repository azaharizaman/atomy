<?php

declare(strict_types=1);

namespace Nexus\Tax\ValueObjects;

use Nexus\Currency\ValueObjects\Money;

/**
 * Tax Breakdown: Complete result of a tax calculation
 * 
 * Contains the calculated tax amount broken down by jurisdiction/level,
 * plus metadata about the calculation.
 * 
 * Supports hierarchical tax structures (children).
 * 
 * Immutable and validated on construction.
 */
final readonly class TaxBreakdown
{
    /**
     * @param Money $netAmount Amount before tax
     * @param Money $totalTaxAmount Total tax amount (sum of all tax lines)
     * @param Money $grossAmount Amount after tax (netAmount + totalTaxAmount)
     * @param array<TaxLine> $taxLines Individual tax lines
     * @param bool $isReverseCharge Whether this is a reverse charge transaction (tax not collected)
     * @param array<string, mixed> $metadata Optional custom metadata
     */
    public function __construct(
        public Money $netAmount,
        public Money $totalTaxAmount,
        public Money $grossAmount,
        public array $taxLines,
        public bool $isReverseCharge = false,
        public array $metadata = [],
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        // Validate all currencies match
        foreach ($this->taxLines as $line) {
            if ($line->amount->getCurrency() !== $this->netAmount->getCurrency()) {
                throw new \InvalidArgumentException(
                    "Tax line currency ({$line->amount->getCurrency()}) does not match net amount currency ({$this->netAmount->getCurrency()})"
                );
            }
        }

        // Validate gross amount = net + total tax
        $expectedGross = $this->netAmount->add($this->totalTaxAmount);
        if (!$this->grossAmount->equals($expectedGross)) {
            throw new \InvalidArgumentException(
                "Gross amount ({$this->grossAmount->getAmount()}) does not equal net + tax ({$expectedGross->getAmount()})"
            );
        }

        // Validate total tax = sum of tax lines
        $calculatedTotal = $this->calculateTotalTax();
        if (!$this->totalTaxAmount->equals($calculatedTotal)) {
            throw new \InvalidArgumentException(
                "Total tax amount ({$this->totalTaxAmount->getAmount()}) does not match sum of tax lines ({$calculatedTotal->getAmount()})"
            );
        }
    }

    /**
     * Calculate total tax from all tax lines (including nested children)
     */
    private function calculateTotalTax(): Money
    {
        $total = Money::of('0.00', $this->netAmount->getCurrency());

        foreach ($this->taxLines as $line) {
            $total = $total->add($line->getTotalWithChildren());
        }

        return $total;
    }

    /**
     * Get tax rate as percentage of net amount
     */
    public function getEffectiveTaxRate(): string
    {
        if ($this->netAmount->isZero()) {
            return '0.0000';
        }

        // Effective rate = (total tax / net amount) × 100
        $rate = bcdiv($this->totalTaxAmount->getAmount(), $this->netAmount->getAmount(), 6);
        return bcmul($rate, '100', 4);
    }

    /**
     * Get all tax lines flattened (including children)
     * 
     * @return array<TaxLine>
     */
    public function getAllTaxLinesFlattened(): array
    {
        $flattened = [];

        foreach ($this->taxLines as $line) {
            $flattened[] = $line;
            $flattened = array_merge($flattened, $line->getAllChildren());
        }

        return $flattened;
    }

    /**
     * Get tax amount by jurisdiction code
     */
    public function getTaxForJurisdiction(string $jurisdictionCode): ?Money
    {
        foreach ($this->getAllTaxLinesFlattened() as $line) {
            if ($line->rate->jurisdictionCode === $jurisdictionCode) {
                return $line->amount;
            }
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'net_amount' => $this->netAmount->getAmount(),
            'total_tax_amount' => $this->totalTaxAmount->getAmount(),
            'gross_amount' => $this->grossAmount->getAmount(),
            'currency' => $this->netAmount->getCurrency(),
            'effective_tax_rate' => $this->getEffectiveTaxRate(),
            'is_reverse_charge' => $this->isReverseCharge,
            'tax_lines' => array_map(fn($line) => $line->toArray(), $this->taxLines),
            'metadata' => $this->metadata,
        ];
    }
}
