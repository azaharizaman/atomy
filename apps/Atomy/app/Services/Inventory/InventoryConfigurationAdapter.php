<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use Nexus\Inventory\Contracts\ConfigurationInterface;
use Nexus\Setting\Services\SettingsManager;

final class InventoryConfigurationAdapter implements ConfigurationInterface
{
    public function __construct(
        private readonly SettingsManager $settings
    ) {}

    public function allowNegativeStock(string $productId, string $warehouseId): bool
    {
        // Check product-specific setting first, then warehouse, then global
        $productSetting = $this->settings->getString("inventory.product.{$productId}.allow_negative", null);
        if ($productSetting !== null) {
            return $productSetting === 'true';
        }

        $warehouseSetting = $this->settings->getString("inventory.warehouse.{$warehouseId}.allow_negative", null);
        if ($warehouseSetting !== null) {
            return $warehouseSetting === 'true';
        }

        return $this->settings->getBool('inventory.allow_negative_stock', false);
    }

    public function getDefaultValuationMethod(): string
    {
        return $this->settings->getString('inventory.default_valuation_method', 'weighted_average');
    }

    public function getDefaultReservationTtl(): int
    {
        return $this->settings->getInt('inventory.default_reservation_ttl_hours', 24);
    }
}
