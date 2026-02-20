<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\ValueObjects;

/**
 * Product Cost Snapshot Value Object
 * 
 * Captures product cost at a point in time with
 * multi-level rollup information.
 */
final readonly class ProductCostSnapshot
{
    public function __construct(
        private string $productId,
        private string $periodId,
        private float $materialCost,
        private float $laborCost,
        private float $overheadCost,
        private float $totalCost,
        private float $unitCost,
        private int $level,
        private \DateTimeImmutable $capturedAt
    ) {}

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getPeriodId(): string
    {
        return $this->periodId;
    }

    public function getMaterialCost(): float
    {
        return $this->materialCost;
    }

    public function getLaborCost(): float
    {
        return $this->laborCost;
    }

    public function getOverheadCost(): float
    {
        return $this->overheadCost;
    }

    public function getTotalCost(): float
    {
        return $this->totalCost;
    }

    public function getUnitCost(): float
    {
        return $this->unitCost;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getCapturedAt(): \DateTimeImmutable
    {
        return $this->capturedAt;
    }

    public function getCostBreakdown(): array
    {
        return [
            'material' => $this->materialCost,
            'labor' => $this->laborCost,
            'overhead' => $this->overheadCost,
            'total' => $this->totalCost,
            'unit' => $this->unitCost,
        ];
    }

    public function isTopLevel(): bool
    {
        return $this->level === 0;
    }
}
