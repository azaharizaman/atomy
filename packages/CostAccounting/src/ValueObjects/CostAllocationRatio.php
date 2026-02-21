<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\ValueObjects;

/**
 * Cost Allocation Ratio Value Object
 * 
 * Immutable value object representing allocation percentages
 * with validation that they sum to 100%.
 */
final readonly class CostAllocationRatio
{
    /** @var array<float> */
    private array $ratios;
    
    /** @var array<string> */
    private array $costCenterIds;

    /**
     * @param array<string, float> $ratios Keyed by cost center ID
     */
    public function __construct(array $ratios)
    {
        $this->validate($ratios);
        
        $this->costCenterIds = array_keys($ratios);
        $this->ratios = array_values($ratios);
    }

    public function getRatios(): array
    {
        return $this->ratios;
    }

    public function getCostCenterIds(): array
    {
        return $this->costCenterIds;
    }

    public function getRatioForCostCenter(string $costCenterId): float
    {
        $index = array_search($costCenterId, $this->costCenterIds, true);
        
        if ($index === false) {
            throw new \InvalidArgumentException(
                "Cost center {$costCenterId} not found in allocation ratios"
            );
        }
        
        return $this->ratios[$index];
    }

    public function calculateAllocation(float $totalAmount): array
    {
        $allocations = [];
        
        foreach ($this->costCenterIds as $index => $costCenterId) {
            $allocations[$costCenterId] = $totalAmount * $this->ratios[$index];
        }
        
        return $allocations;
    }

    public function isValid(): bool
    {
        return abs(array_sum($this->ratios) - 1.0) < 0.0001;
    }

    public function count(): int
    {
        return count($this->ratios);
    }

    private function validate(array $ratios): void
    {
        if (empty($ratios)) {
            throw new \InvalidArgumentException(
                'Allocation ratios cannot be empty'
            );
        }
        
        foreach ($ratios as $costCenterId => $ratio) {
            if ($ratio < 0.0 || $ratio > 1.0) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Allocation ratio for cost center %s must be between 0 and 1, got %f',
                        $costCenterId,
                        $ratio
                    )
                );
            }
        }
        
        $total = array_sum($ratios);
        if (abs($total - 1.0) >= 0.0001) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Allocation ratios must sum to 1.0, got %f',
                    $total
                )
            );
        }
    }
}
