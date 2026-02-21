<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Entities;

use Nexus\CostAccounting\Enums\CostType;

/**
 * Product Cost Entity
 * 
 * Stores calculated product costs with cost rollup
 * information including material, labor, and overhead.
 */
readonly class ProductCost
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
    private CostType $costType;
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
        CostType $costType = CostType::Standard,
        string $currency = 'USD',
        float $materialCost = 0.0,
        float $laborCost = 0.0,
        float $overheadCost = 0.0,
        float $unitCost = 0.0,
        ?\DateTimeImmutable $calculatedAt = null,
        ?\DateTimeImmutable $createdAt = null,
        ?\DateTimeImmutable $updatedAt = null
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
        $this->unitCost = $unitCost;
        $this->calculatedAt = $calculatedAt ?? new \DateTimeImmutable();
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
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

    public function getCostType(): CostType
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
        return $this->costType === CostType::Standard;
    }

    public function isActualCost(): bool
    {
        return $this->costType === CostType::Actual;
    }

    public function withUnitCost(float $quantity): self
    {
        $unitCost = $quantity > 0 ? $this->totalCost / $quantity : 0.0;
        
        return new self(
            $this->id,
            $this->productId,
            $this->costCenterId,
            $this->periodId,
            $this->tenantId,
            $this->costType,
            $this->currency,
            $this->materialCost,
            $this->laborCost,
            $this->overheadCost,
            $unitCost,
            $this->calculatedAt,
            $this->createdAt,
            new \DateTimeImmutable()
        );
    }

    public function withCosts(
        float $materialCost,
        float $laborCost,
        float $overheadCost
    ): self {
        return new self(
            $this->id,
            $this->productId,
            $this->costCenterId,
            $this->periodId,
            $this->tenantId,
            $this->costType,
            $this->currency,
            $materialCost,
            $laborCost,
            $overheadCost,
            $this->unitCost,
            new \DateTimeImmutable(),
            $this->createdAt,
            new \DateTimeImmutable()
        );
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

    public function getLevel(): int
    {
        return 0;
    }
}
