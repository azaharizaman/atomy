<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use Nexus\Inventory\Contracts\StandardCostStorageInterface;
use Nexus\Setting\Services\SettingsManager;

final class StandardCostAdapter implements StandardCostStorageInterface
{
    public function __construct(
        private readonly SettingsManager $settings
    ) {}

    public function getStandardCost(string $productId): float
    {
        return $this->settings->getFloat("inventory.standard_cost.{$productId}", 0.0);
    }

    public function setStandardCost(string $productId, float $cost): void
    {
        $this->settings->set("inventory.standard_cost.{$productId}", (string) $cost);
    }

    public function getCostVariance(string $productId): float
    {
        return $this->settings->getFloat("inventory.cost_variance.{$productId}", 0.0);
    }

    public function addCostVariance(string $productId, float $variance): void
    {
        $current = $this->getCostVariance($productId);
        $new = $current + $variance;
        $this->settings->set("inventory.cost_variance.{$productId}", (string) $new);
    }
}
