<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Sales\Contracts\PriceTierInterface;

class PriceTier extends Model implements PriceTierInterface
{
    use HasUlids;

    protected $table = 'price_tiers';

    public $timestamps = false;

    protected $fillable = [
        'price_list_item_id',
        'min_quantity',
        'max_quantity',
        'unit_price',
        'discount_percentage',
    ];

    protected $casts = [
        'min_quantity' => 'decimal:4',
        'max_quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_percentage' => 'decimal:2',
    ];

    public function priceListItem(): BelongsTo
    {
        return $this->belongsTo(PriceListItem::class, 'price_list_item_id');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPriceListItemId(): string
    {
        return $this->price_list_item_id;
    }

    public function getMinQuantity(): float
    {
        return (float) $this->min_quantity;
    }

    public function getMaxQuantity(): ?float
    {
        return $this->max_quantity ? (float) $this->max_quantity : null;
    }

    public function getUnitPrice(): float
    {
        return (float) $this->unit_price;
    }

    public function getDiscountPercentage(): ?float
    {
        return $this->discount_percentage ? (float) $this->discount_percentage : null;
    }

    public function appliesToQuantity(float $quantity): bool
    {
        $isAboveMin = $quantity >= $this->getMinQuantity();
        $maxQty = $this->getMaxQuantity();
        $isBelowMax = $maxQty === null || $quantity < $maxQty;

        return $isAboveMin && $isBelowMax;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'price_list_item_id' => $this->getPriceListItemId(),
            'min_quantity' => $this->getMinQuantity(),
            'max_quantity' => $this->getMaxQuantity(),
            'unit_price' => $this->getUnitPrice(),
            'discount_percentage' => $this->getDiscountPercentage(),
        ];
    }
}
