<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

interface ProductCostProjection
{
    public function getMaterialCost(): string;
    public function getLaborCost(): string;
    public function getOverheadCost(): string;
    public function getTotalCost(): string;
    public function getCurrency(): string;
    public function getCostMethod(): string;
    public function getLastUpdated(): \DateTimeInterface;

    /**
     * Get cost breakdown by category.
     *
     * @return array<string, float> Keyed array with cost categories (e.g., 'material', 'labor', 'overhead')
     */
    public function getBreakdown(): array;
}
