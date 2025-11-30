<?php

declare(strict_types=1);

namespace Nexus\Laravel\Inventory\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent model for Inventory Lots
 * 
 * @property string $id
 * @property string $product_id
 * @property string $lot_number
 * @property \Illuminate\Support\Carbon|null $manufacture_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property float $initial_quantity
 * @property float $remaining_quantity
 * @property float $unit_cost
 * @property string|null $supplier_id
 * @property string|null $batch_number
 * @property array|null $attributes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Lot extends Model
{
    use HasUlids;

    protected $table = 'inv_lots';

    protected $fillable = [
        'product_id',
        'lot_number',
        'manufacture_date',
        'expiry_date',
        'initial_quantity',
        'remaining_quantity',
        'unit_cost',
        'supplier_id',
        'batch_number',
        'attributes',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'initial_quantity' => 'decimal:4',
        'remaining_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'attributes' => 'array',
    ];

    /**
     * Get stock movements for this lot
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'lot_id');
    }

    /**
     * Get serial numbers for this lot
     */
    public function serials(): HasMany
    {
        return $this->hasMany(Serial::class, 'lot_id');
    }

    /**
     * Check if lot is expired
     */
    public function isExpired(): bool
    {
        if ($this->expiry_date === null) {
            return false;
        }
        return $this->expiry_date->isPast();
    }

    /**
     * Get days until expiry (negative if expired)
     */
    public function getDaysUntilExpiry(): ?int
    {
        if ($this->expiry_date === null) {
            return null;
        }
        return (int) now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Check if lot is exhausted
     */
    public function isExhausted(): bool
    {
        return bccomp((string) $this->remaining_quantity, '0', 4) <= 0;
    }

    public function getExpiryDate(): ?DateTimeImmutable
    {
        if ($this->expiry_date === null) {
            return null;
        }
        return DateTimeImmutable::createFromMutable($this->expiry_date->toDateTime());
    }

    public function getManufactureDate(): ?DateTimeImmutable
    {
        if ($this->manufacture_date === null) {
            return null;
        }
        return DateTimeImmutable::createFromMutable($this->manufacture_date->toDateTime());
    }
}
