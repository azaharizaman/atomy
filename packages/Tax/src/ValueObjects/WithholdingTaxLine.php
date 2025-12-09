<?php

declare(strict_types=1);

namespace Nexus\Tax\ValueObjects;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\Enums\WithholdingTaxType;

/**
 * Withholding Tax Line: Individual WHT line item
 * 
 * Represents a single tax authority's withholding (e.g., federal, state).
 * Multiple lines may exist for multi-level withholding.
 */
final readonly class WithholdingTaxLine
{
    /**
     * @param WithholdingTaxType $taxType Type of withholding (federal, state, local)
     * @param string $authorityCode Tax authority code
     * @param string $authorityName Tax authority name
     * @param float $rate Rate applied (decimal)
     * @param Money $baseAmount Amount the rate was applied to
     * @param Money $taxAmount Calculated tax amount
     * @param string|null $reference Reference number or code
     */
    public function __construct(
        public WithholdingTaxType $taxType,
        public string $authorityCode,
        public string $authorityName,
        public float $rate,
        public Money $baseAmount,
        public Money $taxAmount,
        public ?string $reference = null,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->rate < 0 || $this->rate > 1) {
            throw new \InvalidArgumentException("Rate must be between 0 and 1: {$this->rate}");
        }

        if (empty($this->authorityCode)) {
            throw new \InvalidArgumentException('Authority code cannot be empty');
        }
    }

    /**
     * Get rate as percentage
     */
    public function getRateAsPercentage(): string
    {
        return number_format($this->rate * 100, 2) . '%';
    }
}
