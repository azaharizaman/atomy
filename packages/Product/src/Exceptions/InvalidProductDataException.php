<?php

declare(strict_types=1);

namespace Nexus\Product\Exceptions;

/**
 * Exception thrown when invalid product data is provided
 */
class InvalidProductDataException extends ProductException
{
    public static function emptySkuValue(): self
    {
        return new self("SKU value cannot be empty.");
    }

    public static function skuTooLong(string $sku): self
    {
        $length = strlen($sku);
        return new self("SKU '{$sku}' is too long ({$length} characters). Maximum length is 100 characters.");
    }

    public static function skuContainsInvalidCharacters(string $sku): self
    {
        return new self("SKU '{$sku}' contains invalid control characters.");
    }

    public static function invalidProductType(string $type): self
    {
        return new self("Invalid product type '{$type}'. Must be one of: storable, consumable, service.");
    }

    public static function invalidTrackingMethod(string $method): self
    {
        return new self("Invalid tracking method '{$method}'. Must be one of: none, lot_number, serial_number.");
    }

    public static function emptyProductCode(): self
    {
        return new self("Product code cannot be empty.");
    }

    public static function emptyProductName(): self
    {
        return new self("Product name cannot be empty.");
    }

    public static function dimensionsNotAllowedForService(): self
    {
        return new self("Physical dimensions cannot be set for service products.");
    }

    public static function trackingNotAllowedForService(): self
    {
        return new self("Inventory tracking methods cannot be set for service products.");
    }
}
