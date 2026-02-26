<?php

declare(strict_types=1);

namespace Nexus\ManufacturingOperations\Contracts\Providers;

interface UomProviderInterface
{
    /**
     * Normalize a quantity to the base unit of measure.
     */
    public function normalizeQuantity(string $tenantId, string $productId, float $quantity, string $fromUom): float;

    /**
     * Normalize a price to the base unit of measure.
     */
    public function normalizePrice(string $tenantId, string $productId, string $price, string $fromUom): string;
}
