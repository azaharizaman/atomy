<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nexus\Product\Contracts\ProductVariantInterface;
use Nexus\Product\Enums\BarcodeFormat;
use Nexus\Product\Enums\ProductType;
use Nexus\Product\Enums\TrackingMethod;
use Nexus\Product\ValueObjects\Barcode;
use Nexus\Product\ValueObjects\DimensionSet;
use Nexus\Product\ValueObjects\Sku;

/**
 * Product Variant Model
 *
 * Eloquent implementation of ProductVariantInterface.
 */
class ProductVariant extends Model implements ProductVariantInterface
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'product_variants';

    protected $fillable = [
        'tenant_id',
        'template_id',
        'sku',
        'barcode_value',
        'barcode_format',
        'name',
        'description',
        'type',
        'tracking_method',
        'base_uom',
        'dimensions',
        'category_code',
        'default_revenue_account_code',
        'default_cost_account_code',
        'default_inventory_account_code',
        'is_active',
        'is_saleable',
        'is_purchaseable',
        'attribute_values',
        'metadata',
    ];

    protected $casts = [
        'dimensions' => 'array',
        'attribute_values' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_saleable' => 'boolean',
        'is_purchaseable' => 'boolean',
    ];

    // Relationships

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function template()
    {
        return $this->belongsTo(ProductTemplate::class, 'template_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_code', 'code');
    }

    // Interface implementation

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getTemplateId(): ?string
    {
        return $this->template_id;
    }

    public function getSku(): Sku
    {
        return new Sku($this->sku);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getBarcode(): ?Barcode
    {
        if ($this->barcode_value === null || $this->barcode_format === null) {
            return null;
        }

        return new Barcode(
            $this->barcode_value,
            BarcodeFormat::fromString($this->barcode_format)
        );
    }

    public function getType(): ProductType
    {
        return ProductType::fromString($this->type);
    }

    public function getTrackingMethod(): TrackingMethod
    {
        return TrackingMethod::fromString($this->tracking_method);
    }

    public function getBaseUom(): string
    {
        return $this->base_uom;
    }

    public function getDimensions(): ?DimensionSet
    {
        if ($this->dimensions === null) {
            return null;
        }

        return DimensionSet::fromArray($this->dimensions);
    }

    public function getCategoryCode(): ?string
    {
        return $this->category_code;
    }

    public function getDefaultRevenueAccountCode(): ?string
    {
        return $this->default_revenue_account_code;
    }

    public function getDefaultCostAccountCode(): ?string
    {
        return $this->default_cost_account_code;
    }

    public function getDefaultInventoryAccountCode(): ?string
    {
        return $this->default_inventory_account_code;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isSaleable(): bool
    {
        return $this->is_saleable;
    }

    public function isPurchaseable(): bool
    {
        return $this->is_purchaseable;
    }

    public function getAttributeValues(): array
    {
        return $this->attribute_values ?? [];
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at);
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->updated_at);
    }
}
