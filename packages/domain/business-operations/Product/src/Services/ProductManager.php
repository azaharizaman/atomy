<?php

declare(strict_types=1);

namespace Nexus\Product\Services;

use Nexus\Product\Contracts\CategoryRepositoryInterface;
use Nexus\Product\Contracts\ProductTemplateInterface;
use Nexus\Product\Contracts\ProductTemplateRepositoryInterface;
use Nexus\Product\Contracts\ProductVariantInterface;
use Nexus\Product\Contracts\ProductVariantRepositoryInterface;
use Nexus\Product\Enums\ProductType;
use Nexus\Product\Enums\TrackingMethod;
use Nexus\Product\Exceptions\CategoryNotFoundException;
use Nexus\Product\Exceptions\CircularCategoryReferenceException;
use Nexus\Product\Exceptions\DuplicateBarcodeException;
use Nexus\Product\Exceptions\DuplicateSkuException;
use Nexus\Product\Exceptions\InvalidProductDataException;
use Nexus\Product\Exceptions\ProductNotFoundException;
use Nexus\Product\Exceptions\ProductTemplateNotFoundException;
use Nexus\Product\ValueObjects\Barcode;
use Nexus\Product\ValueObjects\DimensionSet;
use Nexus\Product\ValueObjects\Sku;
use Nexus\Setting\Services\SettingsManager;
use Psr\Log\LoggerInterface;

/**
 * Product Manager Service
 *
 * Orchestrates product template and variant CRUD operations.
 * Enforces business rules and validates data integrity.
 */
class ProductManager
{
    public function __construct(
        private readonly ProductTemplateRepositoryInterface $templateRepository,
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly SkuGenerator $skuGenerator,
        private readonly BarcodeService $barcodeService,
        private readonly SettingsManager $settings,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Create a product template
     *
     * @param string $tenantId
     * @param string $code
     * @param string $name
     * @param string|null $description
     * @param string|null $categoryCode
     * @param array<string, mixed> $metadata
     * @return ProductTemplateInterface
     * @throws InvalidProductDataException
     * @throws CategoryNotFoundException
     */
    public function createTemplate(
        string $tenantId,
        string $code,
        string $name,
        ?string $description = null,
        ?string $categoryCode = null,
        array $metadata = []
    ): ProductTemplateInterface {
        $this->validateTemplateData($code, $name);

        if ($categoryCode !== null) {
            $this->validateCategory($tenantId, $categoryCode);
        }

        $this->logger->info('Creating product template', [
            'tenant_id' => $tenantId,
            'code' => $code,
            'name' => $name,
        ]);

        // Implementation creates entity and saves via repository
        // Actual entity creation happens in Atomy layer
        throw new \RuntimeException('Implementation must create template entity in Atomy layer');
    }

    /**
     * Create a standalone product variant (not linked to template)
     *
     * @param string $tenantId
     * @param string $name
     * @param ProductType $type
     * @param TrackingMethod $trackingMethod
     * @param string $baseUom
     * @param Sku|null $sku Auto-generated if null
     * @param Barcode|null $barcode
     * @param DimensionSet|null $dimensions
     * @param string|null $categoryCode
     * @param string|null $description
     * @param array<string, mixed> $metadata
     * @return ProductVariantInterface
     * @throws InvalidProductDataException
     * @throws DuplicateSkuException
     * @throws DuplicateBarcodeException
     */
    public function createStandaloneVariant(
        string $tenantId,
        string $name,
        ProductType $type,
        TrackingMethod $trackingMethod,
        string $baseUom,
        ?Sku $sku = null,
        ?Barcode $barcode = null,
        ?DimensionSet $dimensions = null,
        ?string $categoryCode = null,
        ?string $description = null,
        array $metadata = []
    ): ProductVariantInterface {
        $this->validateVariantData($name, $type, $trackingMethod, $dimensions);

        // Auto-generate SKU if not provided
        if ($sku === null) {
            $autoGenerate = $this->settings->getBool('product.auto_generate_sku', true);
            if ($autoGenerate) {
                $sku = $this->skuGenerator->generateSku($tenantId, 'PRODUCT');
            } else {
                throw InvalidProductDataException::emptySkuValue();
            }
        }

        // Validate uniqueness
        $this->validateSkuUniqueness($tenantId, $sku);
        
        if ($barcode !== null) {
            $this->barcodeService->ensureUnique($tenantId, $barcode);
        }

        if ($categoryCode !== null) {
            $this->validateCategory($tenantId, $categoryCode);
        }

        $this->logger->info('Creating standalone product variant', [
            'tenant_id' => $tenantId,
            'sku' => $sku->getValue(),
            'name' => $name,
            'type' => $type->value,
        ]);

        // Implementation creates entity and saves via repository
        throw new \RuntimeException('Implementation must create variant entity in Atomy layer');
    }

    /**
     * Update category parent (with circular reference detection)
     *
     * @param string $categoryId
     * @param string|null $newParentId
     * @throws CircularCategoryReferenceException
     * @throws CategoryNotFoundException
     */
    public function updateCategoryParent(string $categoryId, ?string $newParentId): void
    {
        if ($newParentId === null) {
            return; // Setting to root category is always safe
        }

        if ($categoryId === $newParentId) {
            throw CircularCategoryReferenceException::selfReference($categoryId);
        }

        // Check if newParent is a descendant of category
        $ancestorIds = $this->categoryRepository->getAncestorIds($newParentId);
        
        if (in_array($categoryId, $ancestorIds, true)) {
            throw CircularCategoryReferenceException::detected($categoryId, $newParentId);
        }
    }

    /**
     * Find variant by SKU
     *
     * @param string $tenantId
     * @param Sku $sku
     * @return ProductVariantInterface
     * @throws ProductNotFoundException
     */
    public function findVariantBySku(string $tenantId, Sku $sku): ProductVariantInterface
    {
        $variant = $this->variantRepository->findBySku($tenantId, $sku);
        
        if ($variant === null) {
            throw ProductNotFoundException::forSku($sku->getValue());
        }
        
        return $variant;
    }

    /**
     * Find variant by barcode
     *
     * @param string $tenantId
     * @param Barcode $barcode
     * @return ProductVariantInterface
     * @throws ProductNotFoundException
     */
    public function findVariantByBarcode(string $tenantId, Barcode $barcode): ProductVariantInterface
    {
        return $this->barcodeService->lookupVariant($tenantId, $barcode);
    }

    /**
     * Validate template data
     *
     * @param string $code
     * @param string $name
     * @throws InvalidProductDataException
     */
    private function validateTemplateData(string $code, string $name): void
    {
        if (trim($code) === '') {
            throw InvalidProductDataException::emptyProductCode();
        }

        if (trim($name) === '') {
            throw InvalidProductDataException::emptyProductName();
        }
    }

    /**
     * Validate variant data
     *
     * @param string $name
     * @param ProductType $type
     * @param TrackingMethod $trackingMethod
     * @param DimensionSet|null $dimensions
     * @throws InvalidProductDataException
     */
    private function validateVariantData(
        string $name,
        ProductType $type,
        TrackingMethod $trackingMethod,
        ?DimensionSet $dimensions
    ): void {
        if (trim($name) === '') {
            throw InvalidProductDataException::emptyProductName();
        }

        // Service products cannot have dimensions
        if ($type->isService() && $dimensions !== null && $dimensions->hasAnyDimension()) {
            throw InvalidProductDataException::dimensionsNotAllowedForService();
        }

        // Service products cannot have tracking
        if ($type->isService() && $trackingMethod !== TrackingMethod::NONE) {
            throw InvalidProductDataException::trackingNotAllowedForService();
        }
    }

    /**
     * Validate SKU uniqueness
     *
     * @param string $tenantId
     * @param Sku $sku
     * @param string|null $excludeVariantId
     * @throws DuplicateSkuException
     */
    private function validateSkuUniqueness(string $tenantId, Sku $sku, ?string $excludeVariantId = null): void
    {
        if ($this->variantRepository->skuExists($tenantId, $sku, $excludeVariantId)) {
            throw DuplicateSkuException::forSkuInTenant($sku->getValue(), $tenantId);
        }
    }

    /**
     * Validate category exists
     *
     * @param string $tenantId
     * @param string $categoryCode
     * @throws CategoryNotFoundException
     */
    private function validateCategory(string $tenantId, string $categoryCode): void
    {
        $category = $this->categoryRepository->findByCode($tenantId, $categoryCode);
        
        if ($category === null) {
            throw CategoryNotFoundException::forCode($categoryCode);
        }
    }
}
