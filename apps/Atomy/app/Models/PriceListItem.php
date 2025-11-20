<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Sales\Contracts\PriceListItemInterface;
use Nexus\Sales\Contracts\PriceTierInterface;
use Nexus\Sales\ValueObjects\DiscountRule;

class PriceListItem extends Model implements PriceListItemInterface
{
    use HasUlids;

    protected $table = 'price_list_items';

    public $timestamps = false;

    protected $fillable = [
        'price_list_id',
        'product_variant_id',
        'base_price',
        'discount_rule',
    ];

    protected $casts = [
        'base_price' => 'decimal:4',
        'discount_rule' => 'array',
    ];

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(PriceTier::class, 'price_list_item_id');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPriceListId(): string
    {
        return $this->price_list_id;
    }

    public function getProductVariantId(): string
    {
        return $this->product_variant_id;
    }

    public function getBasePrice(): float
    {
        return (float) $this->base_price;
    }

    public function getDiscountRule(): ?DiscountRule
    {
        if ($this->discount_rule === null) {
            return null;
        }

        return DiscountRule::fromArray($this->discount_rule);
    }

    public function getTiers(): array
    {
        return $this->tiers->all();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'price_list_id' => $this->getPriceListId(),
            'product_variant_id' => $this->getProductVariantId(),
            'base_price' => $this->getBasePrice(),
            'discount_rule' => $this->getDiscountRule()?->toArray(),
        ];
    }
}
