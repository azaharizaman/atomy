<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use Nexus\Inventory\Contracts\WeightedAverageStorageInterface;
use Nexus\Setting\Services\SettingsManager;
use Illuminate\Support\Facades\Cache;

final class WeightedAverageAdapter implements WeightedAverageStorageInterface
{
    private const CACHE_PREFIX = 'inventory:wac:';
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly SettingsManager $settings
    ) {}

    public function getAverageCost(string $productId): float
    {
        $cacheKey = self::CACHE_PREFIX . $productId;
        
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return (float) $cached;
        }

        // Fallback to settings if cache miss
        $cost = $this->settings->getFloat("inventory.wac.{$productId}", 0.0);
        Cache::put($cacheKey, $cost, self::CACHE_TTL);
        
        return $cost;
    }

    public function setAverageCost(string $productId, float $cost): void
    {
        $cacheKey = self::CACHE_PREFIX . $productId;
        
        // Store in both cache and persistent settings
        Cache::put($cacheKey, $cost, self::CACHE_TTL);
        $this->settings->set("inventory.wac.{$productId}", (string) $cost);
    }

    public function getTotalQuantity(string $productId): float
    {
        $cacheKey = self::CACHE_PREFIX . $productId . ':qty';
        
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return (float) $cached;
        }

        $qty = $this->settings->getFloat("inventory.wac_qty.{$productId}", 0.0);
        Cache::put($cacheKey, $qty, self::CACHE_TTL);
        
        return $qty;
    }

    public function setTotalQuantity(string $productId, float $quantity): void
    {
        $cacheKey = self::CACHE_PREFIX . $productId . ':qty';
        
        Cache::put($cacheKey, $quantity, self::CACHE_TTL);
        $this->settings->set("inventory.wac_qty.{$productId}", (string) $quantity);
    }
}
