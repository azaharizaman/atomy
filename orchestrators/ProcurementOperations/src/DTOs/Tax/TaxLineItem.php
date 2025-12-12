<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Tax;

use Nexus\Common\ValueObjects\Money;

/**
 * Represents a single tax line item on an invoice.
 */
final readonly class TaxLineItem
{
    public function __construct(
        public string $taxCode,
        public string $taxDescription,
        public float $taxRate,
        public Money $taxableAmount,
        public Money $taxAmount,
        public ?string $jurisdiction = null,
        public bool $isReverseCharge = false,
        public ?string $exemptionReason = null,
    ) {}

    /**
     * Create a standard tax line.
     */
    public static function standard(
        string $taxCode,
        float $taxRate,
        Money $taxableAmount,
        Money $taxAmount,
        string $description = '',
    ): self {
        return new self(
            taxCode: $taxCode,
            taxDescription: $description ?: "Tax at {$taxRate}%",
            taxRate: $taxRate,
            taxableAmount: $taxableAmount,
            taxAmount: $taxAmount,
        );
    }

    /**
     * Create a VAT line item.
     */
    public static function vat(
        float $rate,
        Money $taxableAmount,
        Money $taxAmount,
        string $jurisdiction,
    ): self {
        return new self(
            taxCode: "VAT-{$rate}",
            taxDescription: "VAT at {$rate}%",
            taxRate: $rate,
            taxableAmount: $taxableAmount,
            taxAmount: $taxAmount,
            jurisdiction: $jurisdiction,
        );
    }

    /**
     * Create a reverse charge VAT line.
     */
    public static function reverseCharge(
        float $rate,
        Money $taxableAmount,
        string $jurisdiction,
    ): self {
        return new self(
            taxCode: "VAT-RC-{$rate}",
            taxDescription: "Reverse Charge VAT at {$rate}%",
            taxRate: $rate,
            taxableAmount: $taxableAmount,
            taxAmount: Money::of(0, $taxableAmount->getCurrency()),
            jurisdiction: $jurisdiction,
            isReverseCharge: true,
        );
    }

    /**
     * Create an exempt line item.
     */
    public static function exempt(
        Money $taxableAmount,
        string $exemptionReason,
        string $jurisdiction,
    ): self {
        return new self(
            taxCode: 'TAX-EXEMPT',
            taxDescription: 'Tax Exempt',
            taxRate: 0.0,
            taxableAmount: $taxableAmount,
            taxAmount: Money::of(0, $taxableAmount->getCurrency()),
            jurisdiction: $jurisdiction,
            exemptionReason: $exemptionReason,
        );
    }

    /**
     * Check if tax is calculated correctly within tolerance.
     */
    public function isCalculationCorrect(float $tolerancePercent = 0.01): bool
    {
        if ($this->taxRate === 0.0) {
            return $this->taxAmount->isZero();
        }

        $expectedTax = $this->taxableAmount->multiply($this->taxRate / 100);
        $difference = abs($this->taxAmount->getAmount() - $expectedTax->getAmount());
        $tolerance = $this->taxableAmount->getAmount() * ($tolerancePercent / 100);

        return $difference <= $tolerance;
    }

    /**
     * Calculate the expected tax amount.
     */
    public function calculateExpectedTax(): Money
    {
        return $this->taxableAmount->multiply($this->taxRate / 100);
    }

    /**
     * Get the variance between stated and calculated tax.
     */
    public function getTaxVariance(): Money
    {
        $expected = $this->calculateExpectedTax();
        return Money::of(
            $this->taxAmount->getAmount() - $expected->getAmount(),
            $this->taxAmount->getCurrency(),
        );
    }
}
