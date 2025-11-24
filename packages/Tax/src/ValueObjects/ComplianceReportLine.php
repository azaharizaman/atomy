<?php

declare(strict_types=1);

namespace Nexus\Tax\ValueObjects;

/**
 * Compliance Report Line: Single line in a tax compliance report
 * 
 * Generic structure for tax reporting (VAT returns, sales tax filings, etc.)
 * Supports hierarchical reporting with parent-child relationships.
 * 
 * Immutable and validated on construction.
 */
final readonly class ComplianceReportLine
{
    /**
     * @param string $lineCode Report line code (e.g., "box_1", "line_10")
     * @param string $description Human-readable description
     * @param string $amount Amount for this line (BCMath string)
     * @param string|null $taxCode Related tax code (if applicable)
     * @param string|null $jurisdictionCode Related jurisdiction (if applicable)
     * @param ComplianceReportLine|null $parent Parent line (for hierarchical reports)
     * @param array<ComplianceReportLine> $children Child lines
     * @param array<string, mixed> $metadata Optional custom metadata
     */
    public function __construct(
        public string $lineCode,
        public string $description,
        public string $amount,
        public ?string $taxCode = null,
        public ?string $jurisdictionCode = null,
        public ?ComplianceReportLine $parent = null,
        public array $children = [],
        public array $metadata = [],
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->lineCode)) {
            throw new \InvalidArgumentException('Line code cannot be empty');
        }

        if (empty($this->description)) {
            throw new \InvalidArgumentException('Description cannot be empty');
        }

        if (!is_numeric($this->amount)) {
            throw new \InvalidArgumentException("Amount must be numeric string: {$this->amount}");
        }
    }

    /**
     * Get total amount including all children
     */
    public function getTotalWithChildren(): string
    {
        $total = $this->amount;

        foreach ($this->children as $child) {
            $total = bcadd($total, $child->getTotalWithChildren(), 4);
        }

        return $total;
    }

    /**
     * Get all children recursively (flattened)
     * 
     * @return array<ComplianceReportLine>
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
            'line_code' => $this->lineCode,
            'description' => $this->description,
            'amount' => $this->amount,
            'tax_code' => $this->taxCode,
            'jurisdiction_code' => $this->jurisdictionCode,
            'parent_line_code' => $this->parent?->lineCode,
            'has_children' => $this->hasChildren(),
            'children' => array_map(fn($child) => $child->toArray(), $this->children),
            'metadata' => $this->metadata,
        ];
    }
}
