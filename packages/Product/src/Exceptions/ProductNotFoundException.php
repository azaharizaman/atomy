<?php

declare(strict_types=1);

namespace Nexus\Product\Exceptions;

/**
 * Exception thrown when a product variant is not found
 */
class ProductNotFoundException extends ProductException
{
    public static function forId(string $id): self
    {
        return new self("Product variant with ID '{$id}' not found.");
    }

    public static function forSku(string $sku): self
    {
        return new self("Product variant with SKU '{$sku}' not found.");
    }

    public static function forBarcode(string $barcode): self
    {
        return new self("Product variant with barcode '{$barcode}' not found.");
    }
}
