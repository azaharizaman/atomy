<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Entities;

/**
 * Product Cost Entity
 * 
 * Stores calculated product costs with cost rollup
 * information including material, labor, and overhead.
 */
class ProductCost
{
    private string $id;
    private string $productId;
    private string $costCenterId;
    private string $periodId;
    private float $materialCost;
    private float $laborCost;
    private float $overheadCost;
    private float $totalCost;
    private float $unitCost;
    private string $costType; // actual, standard
    private string $currency;
    private string $tenantId;
    private \DateTimeImmutable $calculatedAt;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $id,
        string $productId,
        string $costCenterId,
        string $periodId,
        string $tenantId,
        string $costType = 'standard',
        string $currency = 'USD',
        float $materialCost = 0.0,
        float $laborCost = 0.0,
        float $overheadCost = 0.0
    ) {
        $this->id = $id;
        $this->productId = $productId;
        $this->costCenterId = $costCenterId;
        $this->periodId = $periodId;
        $this->tenantId = $tenantId;
        $this->costType = $costType;
        $this->currency = $currency;
        $this->materialCost = $materialCost;
        $this->laborCost = $laborCost;
        $this->overheadCost = $overheadCost;
        $this->totalCost = $materialCost + $laborCost + $overheadCost;
        $this->unitCost = 0.0;
        $this->calculatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getCostCenterId(): string
    {
        return $this->costCenterId;
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

    public function getCostType(): string
    {
        return $this->costType;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getCalculatedAt(): \DateTimeImmutable
    {
        return $this->calculatedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isStandardCost(): bool
    {
        return $this->costType === 'standard';
    }

    public function isActualCost(): bool
    {
        return $this->costType === 'actual';
    }

    public function calculateUnitCost(float $quantity): void
    {
        $this->unitCost = $quantity > 0 ? $this->totalCost / $quantity : 0.0;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function updateCosts(
        float $materialCost,
        float $laborCost,
        float $overheadCost
    ): void {
        $this->materialCost = $materialCost;
        $this->laborCost = $laborCost;
        $this->overheadCost = $overheadCost;
        $this->totalCost = $materialCost + $laborCost + $overheadCost;
        $this->calculatedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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
}
