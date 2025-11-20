<?php

declare(strict_types=1);

namespace Nexus\Product\Services;

use Nexus\Product\Contracts\ProductVariantRepositoryInterface;
use Nexus\Product\Enums\BarcodeFormat;
use Nexus\Product\Exceptions\DuplicateBarcodeException;
use Nexus\Product\Exceptions\ProductNotFoundException;
use Nexus\Product\ValueObjects\Barcode;

/**
 * Barcode Service
 *
 * Handles barcode validation, format conversion, and lookups.
 */
class BarcodeService
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $variantRepository
    ) {}

    /**
     * Validate a barcode
     *
     * @param Barcode $barcode
     * @return bool
     */
    public function validate(Barcode $barcode): bool
    {
        // Validation is already done in Barcode value object constructor
        // This method exists for explicit validation calls
        return true;
    }

    /**
     * Look up product variant by barcode
     *
     * @param string $tenantId
     * @param Barcode $barcode
     * @return ProductVariantInterface
     * @throws ProductNotFoundException
     */
    public function lookupVariant(string $tenantId, Barcode $barcode)
    {
        $variant = $this->variantRepository->findByBarcode($tenantId, $barcode);
        
        if ($variant === null) {
            throw ProductNotFoundException::forBarcode($barcode->getValue());
        }
        
        return $variant;
    }

    /**
     * Check if barcode is unique in tenant
     *
     * @param string $tenantId
     * @param Barcode $barcode
     * @param string|null $excludeVariantId
     * @return bool
     */
    public function isUnique(string $tenantId, Barcode $barcode, ?string $excludeVariantId = null): bool
    {
        return !$this->variantRepository->barcodeExists($tenantId, $barcode, $excludeVariantId);
    }

    /**
     * Ensure barcode is unique, throw exception if duplicate
     *
     * @param string $tenantId
     * @param Barcode $barcode
     * @param string|null $excludeVariantId
     * @throws DuplicateBarcodeException
     */
    public function ensureUnique(string $tenantId, Barcode $barcode, ?string $excludeVariantId = null): void
    {
        if (!$this->isUnique($tenantId, $barcode, $excludeVariantId)) {
            throw DuplicateBarcodeException::forBarcodeInTenant($barcode->getValue(), $tenantId);
        }
    }

    /**
     * Convert UPC-A to EAN-13 (add leading zero)
     *
     * @param Barcode $upcA
     * @return Barcode
     */
    public function upcToEan13(Barcode $upcA): Barcode
    {
        if ($upcA->getFormat() !== BarcodeFormat::UPCA) {
            return $upcA;
        }
        
        $ean13Value = '0' . $upcA->getValue();
        return new Barcode($ean13Value, BarcodeFormat::EAN13);
    }

    /**
     * Try to convert EAN-13 to UPC-A (remove leading zero if present)
     *
     * @param Barcode $ean13
     * @return Barcode|null Null if conversion not possible
     */
    public function ean13ToUpc(?Barcode $ean13): ?Barcode
    {
        if ($ean13 === null || $ean13->getFormat() !== BarcodeFormat::EAN13) {
            return null;
        }
        
        if (!str_starts_with($ean13->getValue(), '0')) {
            return null;
        }
        
        $upcValue = substr($ean13->getValue(), 1);
        
        try {
            return new Barcode($upcValue, BarcodeFormat::UPCA);
        } catch (\Exception) {
            return null;
        }
    }
}
