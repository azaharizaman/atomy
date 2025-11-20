<?php

declare(strict_types=1);

namespace Nexus\Sales\Exceptions;

/**
 * Exception thrown when price is not found for a product.
 */
class PriceNotFoundException extends SalesException
{
    public static function forProduct(string $productVariantId, string $customerId, string $currencyCode): self
    {
        return new self(
            "No price found for product '{$productVariantId}' " .
            "for customer '{$customerId}' in currency '{$currencyCode}'."
        );
    }

    public static function forPriceList(string $priceListId, string $productVariantId): self
    {
        return new self(
            "Product '{$productVariantId}' not found in price list '{$priceListId}'."
        );
    }
}
