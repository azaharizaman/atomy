<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\ValueObjects;

use Nexus\CostAccounting\Contracts\CostVarianceInterface;

/**
 * Cost Variance Breakdown Value Object
 * 
 * Tracks variance between actual and standard costs
 * with breakdown by type.
 */
final readonly class CostVarianceBreakdown implements CostVarianceInterface
{
    public function __construct(
        private string $id,
        private string $productId,
        private string $costCenterId,
        private string $periodId,
        private string $tenantId,
        private float $priceVariance,
        private float $rateVariance,
        private float $efficiencyVariance,
        private float $totalVariance,
        private float $variancePercentage,
        private float $materialVariance,
        private float $laborVariance,
        private float $overheadVariance,
        private float $baselineCost = 0.0
    ) {}

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCostCenterId(): string
    {
        return $this->costCenterId;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getPeriodId(): string
    {
        return $this->periodId;
    }

    public function getPriceVariance(): float
    {
        return $this->priceVariance;
    }

    public function getRateVariance(): float
    {
        return $this->rateVariance;
    }

    public function getEfficiencyVariance(): float
    {
        return $this->efficiencyVariance;
    }

    public function getTotalVariance(): float
    {
        return $this->totalVariance;
    }

    public function getVariancePercentage(): float
    {
        return $this->variancePercentage;
    }

    public function getBaselineCost(): float
    {
        return $this->baselineCost;
    }

    public function getMaterialVariance(): float
    {
        return $this->materialVariance;
    }

    public function getLaborVariance(): float
    {
        return $this->laborVariance;
    }

    public function getOverheadVariance(): float
    {
        return $this->overheadVariance;
    }

    public function isFavorable(): bool
    {
        return $this->totalVariance < 0;
    }

    public function isUnfavorable(): bool
    {
        return $this->totalVariance > 0;
    }

    /**
     * Get the variance breakdown as an associative array.
     *
     * @return array{
     *     price: float,
     *     rate: float,
     *     efficiency: float,
     *     material: float,
     *     labor: float,
     *     overhead: float,
     *     total: float,
     *     percentage: float,
     *     baseline: float
     * }
     */
    public function getBreakdown(): array
    {
        return [
            'price' => $this->priceVariance,
            'rate' => $this->rateVariance,
            'efficiency' => $this->efficiencyVariance,
            'material' => $this->materialVariance,
            'labor' => $this->laborVariance,
            'overhead' => $this->overheadVariance,
            'total' => $this->totalVariance,
            'percentage' => $this->variancePercentage,
            'baseline' => $this->baselineCost,
        ];
    }
}
