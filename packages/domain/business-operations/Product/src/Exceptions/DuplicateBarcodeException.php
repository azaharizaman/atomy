<?php

declare(strict_types=1);

namespace Nexus\Product\Exceptions;

/**
 * Exception thrown when attempting to create a duplicate barcode
 */
class DuplicateBarcodeException extends ProductException
{
    public static function forBarcode(string $barcode): self
    {
        return new self("Barcode '{$barcode}' already exists. Barcodes must be unique.");
    }

    public static function forBarcodeInTenant(string $barcode, string $tenantId): self
    {
        return new self("Barcode '{$barcode}' already exists in tenant '{$tenantId}'. Barcodes must be unique within tenant scope.");
    }
}
