<?php

declare(strict_types=1);

namespace Nexus\Tax\ValueObjects;

use Nexus\Tax\Enums\TaxLevel;
use Nexus\Tax\Enums\TaxType;

/**
 * Tax Rate: A single tax rate for a specific jurisdiction and time period
 * 
 * Represents a tax rate that is valid for a specific date range.
 * All monetary calculations use BCMath for precision.
 * 
 * Immutable and validated on construction.
 */
final readonly class TaxRate
{
    /**
     * @param string $taxCode Unique tax code (e.g., "US-CA-SALES", "EU-DE-VAT")
     * @param string $name Human-readable name (e.g., "California State Sales Tax")
     * @param string $rate Tax rate as decimal string (e.g., "0.0725" for 7.25%) - MUST use BCMath
     * @param TaxType $type Type of tax
     * @param TaxLevel $level Jurisdiction level (Federal, State, Local, Municipal)
     * @param string $jurisdictionCode Jurisdiction code (e.g., "US-CA", "EU-DE")
     * @param \DateTimeImmutable $effectiveFrom Start date when this rate becomes effective
     * @param \DateTimeImmutable|null $effectiveTo Optional end date when rate is superseded
     * @param string|null $glAccountCode Optional GL account code for posting
     * @param array<string, mixed> $metadata Optional custom metadata
     */
    public function __construct(
        public string $taxCode,
        public string $name,
        public string $rate,
        public TaxType $type,
        public TaxLevel $level,
        public string $jurisdictionCode,
        public \DateTimeImmutable $effectiveFrom,
        public ?\DateTimeImmutable $effectiveTo = null,
        public ?string $glAccountCode = null,
        public array $metadata = [],
    ) {
        $this->validate();
    }

    /**
     * Validate tax rate
     * 
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        if (empty($this->taxCode)) {
            throw new \InvalidArgumentException('Tax code cannot be empty');
        }

        if (empty($this->name)) {
            throw new \InvalidArgumentException('Tax name cannot be empty');
        }

        if (empty($this->jurisdictionCode)) {
            throw new \InvalidArgumentException('Jurisdiction code cannot be empty');
        }

        // Validate rate is numeric string
        if (!is_numeric($this->rate)) {
            throw new \InvalidArgumentException("Tax rate must be numeric string, got: {$this->rate}");
        }

        // Validate rate is non-negative
        if (bccomp($this->rate, '0', 4) < 0) {
            throw new \InvalidArgumentException("Tax rate cannot be negative: {$this->rate}");
        }

        // Validate rate is reasonable (0% to 100%)
        if (bccomp($this->rate, '1.0000', 4) > 0) {
            throw new \InvalidArgumentException("Tax rate cannot exceed 100% (1.0000): {$this->rate}");
        }

        // Validate effective date range
        if ($this->effectiveTo !== null && $this->effectiveTo <= $this->effectiveFrom) {
            throw new \InvalidArgumentException(
                "effectiveTo ({$this->effectiveTo->format('Y-m-d')}) must be after effectiveFrom ({$this->effectiveFrom->format('Y-m-d')})"
            );
        }
    }

    /**
     * Check if this rate is effective on a given date
     */
    public function isEffectiveOn(\DateTimeInterface $date): bool
    {
        // Convert to DateTimeImmutable for comparison
        $checkDate = $date instanceof \DateTimeImmutable 
            ? $date 
            : \DateTimeImmutable::createFromInterface($date);

        // Check if date is on or after effectiveFrom
        if ($checkDate < $this->effectiveFrom) {
            return false;
        }

        // Check if date is before effectiveTo (if set)
        if ($this->effectiveTo !== null && $checkDate >= $this->effectiveTo) {
            return false;
        }

        return true;
    }

    /**
     * Calculate tax amount for a given taxable base
     * 
     * @param string $taxableBase Base amount to calculate tax on (BCMath string)
     * @return string Tax amount (BCMath string with 4 decimal precision)
     */
    public function calculateTaxAmount(string $taxableBase): string
    {
        if (!is_numeric($taxableBase)) {
            throw new \InvalidArgumentException("Taxable base must be numeric string, got: {$taxableBase}");
        }

        // Tax amount = taxable base × tax rate
        return bcmul($taxableBase, $this->rate, 4);
    }

    /**
     * Get rate as percentage (e.g., "7.2500" for 7.25%)
     */
    public function getPercentage(): string
    {
        return bcmul($this->rate, '100', 4);
    }

    /**
     * Check if rate is zero (0%)
     */
    public function isZeroRate(): bool
    {
        return bccomp($this->rate, '0', 4) === 0;
    }

    /**
     * Create a superseding rate (new rate that replaces this one)
     * 
     * Useful when tax rates change - creates new rate with updated effectiveFrom
     * and sets this rate's effectiveTo to the new rate's effectiveFrom.
     */
    public function supersede(
        string $newRate,
        \DateTimeImmutable $newEffectiveFrom,
        ?string $newGlAccountCode = null
    ): array {
        // Close current rate
        $closedRate = new self(
            taxCode: $this->taxCode,
            name: $this->name,
            rate: $this->rate,
            type: $this->type,
            level: $this->level,
            jurisdictionCode: $this->jurisdictionCode,
            effectiveFrom: $this->effectiveFrom,
            effectiveTo: $newEffectiveFrom, // Close on day new rate starts
            glAccountCode: $this->glAccountCode,
            metadata: $this->metadata,
        );

        // Create new rate
        $newRateObject = new self(
            taxCode: $this->taxCode,
            name: $this->name,
            rate: $newRate,
            type: $this->type,
            level: $this->level,
            jurisdictionCode: $this->jurisdictionCode,
            effectiveFrom: $newEffectiveFrom,
            effectiveTo: null,
            glAccountCode: $newGlAccountCode ?? $this->glAccountCode,
            metadata: $this->metadata,
        );

        return [$closedRate, $newRateObject];
    }

    /**
     * Convert to array for logging/debugging
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tax_code' => $this->taxCode,
            'name' => $this->name,
            'rate' => $this->rate,
            'percentage' => $this->getPercentage(),
            'type' => $this->type->value,
            'level' => $this->level->value,
            'jurisdiction_code' => $this->jurisdictionCode,
            'effective_from' => $this->effectiveFrom->format('Y-m-d'),
            'effective_to' => $this->effectiveTo?->format('Y-m-d'),
            'gl_account_code' => $this->glAccountCode,
            'metadata' => $this->metadata,
        ];
    }
}
