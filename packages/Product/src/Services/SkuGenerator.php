<?php

declare(strict_types=1);

namespace Nexus\Product\Services;

use Nexus\Product\ValueObjects\Sku;
use Nexus\Sequencing\Contracts\SequenceGeneratorInterface;

/**
 * SKU Generator Service
 *
 * Generates unique Stock Keeping Unit identifiers using the Sequencing package.
 */
class SkuGenerator
{
    public function __construct(
        private readonly SequenceGeneratorInterface $sequenceGenerator
    ) {}

    /**
     * Generate a new SKU for a product variant
     *
     * @param string $tenantId
     * @param string $scope Sequence scope (e.g., 'PRODUCT', 'PRODUCT_VARIANT')
     * @param string|null $prefix Optional prefix for SKU
     * @return Sku
     */
    public function generateSku(string $tenantId, string $scope = 'PRODUCT', ?string $prefix = null): Sku
    {
        $sequenceValue = $this->sequenceGenerator->generateNext($scope, $tenantId);
        
        $skuValue = $prefix !== null 
            ? "{$prefix}-{$sequenceValue}"
            : $sequenceValue;
        
        return new Sku($skuValue);
    }

    /**
     * Generate SKU with custom pattern
     *
     * @param string $tenantId
     * @param string $scope
     * @param string $pattern Pattern with {seq} placeholder (e.g., "PRD-{seq}-CUSTOM")
     * @return Sku
     */
    public function generateWithPattern(string $tenantId, string $scope, string $pattern): Sku
    {
        $sequenceValue = $this->sequenceGenerator->generateNext($scope, $tenantId);
        $skuValue = str_replace('{seq}', $sequenceValue, $pattern);
        
        return new Sku($skuValue);
    }
}
