<?php

declare(strict_types=1);

namespace Nexus\Product\Exceptions;

/**
 * Exception thrown when attempting to create a duplicate SKU
 */
class DuplicateSkuException extends ProductException
{
    public static function forSku(string $sku): self
    {
        return new self("SKU '{$sku}' already exists. SKUs must be unique.");
    }

    public static function forSkuInTenant(string $sku, string $tenantId): self
    {
        return new self("SKU '{$sku}' already exists in tenant '{$tenantId}'. SKUs must be unique within tenant scope.");
    }
}
