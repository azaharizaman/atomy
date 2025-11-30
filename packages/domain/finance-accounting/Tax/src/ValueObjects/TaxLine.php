<?php

declare(strict_types=1);

namespace Nexus\Tax\ValueObjects;

use Nexus\Currency\ValueObjects\Money;

/**
 * Tax Line: Individual tax line in a tax calculation
 * 
 * Represents a single tax component (e.g., Federal GST, State PST).
 * Supports hierarchical children for cascading taxes.
 * 
 * Immutable and validated on construction.
 */
final readonly class TaxLine
{
    /**
     * @param TaxRate $rate Tax rate applied
     * @param Money $taxableBase Base amount tax was calculated on
     * @param Money $amount Tax amount calculated
     * @param string $description Human-readable description
     * @param string|null $glAccountCode GL account code for posting
     * @param array<TaxLine> $children Nested child tax lines (for cascading taxes)
     * @param array<string, mixed> $metadata Optional custom metadata
     */
    public function __construct(
        public TaxRate $rate,
        public Money $taxableBase,
        public Money $amount,
        public string $description,
        public ?string $glAccountCode = null,
        public array $children = [],
        public array $metadata = [],
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->description)) {
            throw new \InvalidArgumentException('Tax line description cannot be empty');
        }

        // Validate currencies match
        if ($this->amount->getCurrency() !== $this->taxableBase->getCurrency()) {
            throw new \InvalidArgumentException(
                "Tax amount currency ({$this->amount->getCurrency()}) does not match taxable base currency ({$this->taxableBase->getCurrency()})"
            );
        }

        // Validate amount = taxable base × rate
        $expectedAmount = $this->rate->calculateTaxAmount($this->taxableBase->getAmount());
        if (bccomp($this->amount->getAmount(), $expectedAmount, 4) !== 0) {
            throw new \InvalidArgumentException(
                "Tax amount ({$this->amount->getAmount()}) does not match taxable base × rate ({$expectedAmount})"
            );
        }

        // Validate children
        foreach ($this->children as $child) {
            if ($child->amount->getCurrency() !== $this->amount->getCurrency()) {
                throw new \InvalidArgumentException(
                    "Child tax line currency ({$child->amount->getCurrency()}) does not match parent currency ({$this->amount->getCurrency()})"
                );
            }
        }
    }

    /**
     * Get total amount including all children
     */
    public function getTotalWithChildren(): Money
    {
        $total = $this->amount;

        foreach ($this->children as $child) {
            $total = $total->add($child->getTotalWithChildren());
        }

        return $total;
    }

    /**
     * Get all children recursively (flattened)
     * 
     * @return array<TaxLine>
     */
    public function getAllChildren(): array
    {
        $all = [];

        foreach ($this->children as $child) {
            $all[] = $child;
            $all = array_merge($all, $child->getAllChildren());
        }

        return $all;
    }

    /**
     * Check if this line has children
     */
    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    /**
     * Get depth in hierarchy (0 = top level)
     */
    public function getDepth(): int
    {
        if (!$this->hasChildren()) {
            return 0;
        }

        $maxChildDepth = 0;
        foreach ($this->children as $child) {
            $maxChildDepth = max($maxChildDepth, $child->getDepth());
        }

        return $maxChildDepth + 1;
    }

    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'tax_code' => $this->rate->taxCode,
            'rate_percentage' => $this->rate->getPercentage(),
            'taxable_base' => $this->taxableBase->getAmount(),
            'amount' => $this->amount->getAmount(),
            'currency' => $this->amount->getCurrency(),
            'gl_account_code' => $this->glAccountCode ?? $this->rate->glAccountCode,
            'jurisdiction_code' => $this->rate->jurisdictionCode,
            'tax_level' => $this->rate->level->value,
            'has_children' => $this->hasChildren(),
            'children' => array_map(fn($child) => $child->toArray(), $this->children),
            'metadata' => $this->metadata,
        ];
    }
}
