<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Sales\Contracts\PriceListInterface;
use Nexus\Sales\Contracts\PriceListItemInterface;
use Nexus\Sales\Enums\PricingStrategy;

class PriceList extends Model implements PriceListInterface
{
    use HasUlids;

    protected $table = 'sales_price_lists';

    protected $fillable = [
        'tenant_id',
        'name',
        'currency_code',
        'strategy',
        'valid_from',
        'valid_until',
        'customer_id',
        'is_active',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class, 'price_list_id');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenant_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCurrencyCode(): string
    {
        return $this->currency_code;
    }

    public function getStrategy(): PricingStrategy
    {
        return PricingStrategy::from($this->strategy);
    }

    public function getValidFrom(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->valid_from);
    }

    public function getValidUntil(): ?DateTimeImmutable
    {
        return $this->valid_until
            ? DateTimeImmutable::createFromMutable($this->valid_until)
            : null;
    }

    public function getCustomerId(): ?string
    {
        return $this->customer_id;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getItems(): array
    {
        return $this->items->all();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at);
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->updated_at);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'name' => $this->getName(),
            'currency_code' => $this->getCurrencyCode(),
            'strategy' => $this->getStrategy()->value,
            'valid_from' => $this->getValidFrom()->format('Y-m-d'),
            'valid_until' => $this->getValidUntil()?->format('Y-m-d'),
            'customer_id' => $this->getCustomerId(),
            'is_active' => $this->isActive(),
            'created_at' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $this->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
