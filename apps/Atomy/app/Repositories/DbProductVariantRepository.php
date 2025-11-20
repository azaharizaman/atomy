<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ProductVariant;
use Nexus\Product\Contracts\ProductVariantInterface;
use Nexus\Product\Contracts\ProductVariantRepositoryInterface;
use Nexus\Product\ValueObjects\Barcode;
use Nexus\Product\ValueObjects\Sku;

/**
 * Database Product Variant Repository
 *
 * Eloquent implementation of ProductVariantRepositoryInterface.
 */
class DbProductVariantRepository implements ProductVariantRepositoryInterface
{
    public function findById(string $id): ?ProductVariantInterface
    {
        return ProductVariant::find($id);
    }

    public function findBySku(string $tenantId, Sku $sku): ?ProductVariantInterface
    {
        return ProductVariant::where('tenant_id', $tenantId)
            ->where('sku', $sku->getValue())
            ->first();
    }

    public function findByBarcode(string $tenantId, Barcode $barcode): ?ProductVariantInterface
    {
        return ProductVariant::where('tenant_id', $tenantId)
            ->where('barcode_value', $barcode->getValue())
            ->first();
    }

    public function getByTemplate(string $templateId, bool $activeOnly = true): array
    {
        $query = ProductVariant::where('template_id', $templateId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('name')
            ->get()
            ->all();
    }

    public function getAllForTenant(string $tenantId, bool $activeOnly = true): array
    {
        $query = ProductVariant::where('tenant_id', $tenantId);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('name')
            ->get()
            ->all();
    }

    public function getByCategory(string $tenantId, string $categoryCode, bool $activeOnly = true): array
    {
        $query = ProductVariant::where('tenant_id', $tenantId)
            ->where('category_code', $categoryCode);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('name')
            ->get()
            ->all();
    }

    public function getStandaloneVariants(string $tenantId, bool $activeOnly = true): array
    {
        $query = ProductVariant::where('tenant_id', $tenantId)
            ->whereNull('template_id');

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('name')
            ->get()
            ->all();
    }

    public function skuExists(string $tenantId, Sku $sku, ?string $excludeVariantId = null): bool
    {
        $query = ProductVariant::where('tenant_id', $tenantId)
            ->where('sku', $sku->getValue());

        if ($excludeVariantId !== null) {
            $query->where('id', '!=', $excludeVariantId);
        }

        return $query->exists();
    }

    public function barcodeExists(string $tenantId, Barcode $barcode, ?string $excludeVariantId = null): bool
    {
        $query = ProductVariant::where('tenant_id', $tenantId)
            ->where('barcode_value', $barcode->getValue());

        if ($excludeVariantId !== null) {
            $query->where('id', '!=', $excludeVariantId);
        }

        return $query->exists();
    }

    public function save(ProductVariantInterface $variant): ProductVariantInterface
    {
        if ($variant instanceof ProductVariant) {
            $variant->save();
            return $variant;
        }

        // Create new variant from interface
        $model = new ProductVariant();
        $model->tenant_id = $variant->getTenantId();
        $model->template_id = $variant->getTemplateId();
        $model->sku = $variant->getSku()->getValue();
        
        $barcode = $variant->getBarcode();
        if ($barcode !== null) {
            $model->barcode_value = $barcode->getValue();
            $model->barcode_format = $barcode->getFormat()->value;
        }
        
        $model->name = $variant->getName();
        $model->description = $variant->getDescription();
        $model->type = $variant->getType()->value;
        $model->tracking_method = $variant->getTrackingMethod()->value;
        $model->base_uom = $variant->getBaseUom();
        
        $dimensions = $variant->getDimensions();
        if ($dimensions !== null) {
            $model->dimensions = $dimensions->toArray();
        }
        
        $model->category_code = $variant->getCategoryCode();
        $model->default_revenue_account_code = $variant->getDefaultRevenueAccountCode();
        $model->default_cost_account_code = $variant->getDefaultCostAccountCode();
        $model->default_inventory_account_code = $variant->getDefaultInventoryAccountCode();
        $model->is_active = $variant->isActive();
        $model->is_saleable = $variant->isSaleable();
        $model->is_purchaseable = $variant->isPurchaseable();
        $model->attribute_values = $variant->getAttributeValues();
        $model->metadata = $variant->getMetadata();
        $model->save();

        return $model;
    }

    public function delete(string $id): bool
    {
        $variant = ProductVariant::find($id);
        return $variant?->delete() ?? false;
    }

    public function countByTemplate(string $templateId): int
    {
        return ProductVariant::where('template_id', $templateId)->count();
    }
}
