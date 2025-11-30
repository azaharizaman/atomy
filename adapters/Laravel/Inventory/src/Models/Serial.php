<?php

declare(strict_types=1);

namespace Nexus\Laravel\Inventory\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model for Serial Numbers
 * 
 * @property string $id
 * @property string $product_id
 * @property string $serial_number
 * @property string $warehouse_id
 * @property string|null $lot_id
 * @property string $status
 * @property float $cost
 * @property string|null $current_owner_id
 * @property string|null $current_owner_type
 * @property array|null $attributes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Serial extends Model
{
    use HasUlids;

    protected $table = 'inv_serials';

    protected $fillable = [
        'product_id',
        'serial_number',
        'warehouse_id',
        'lot_id',
        'status',
        'cost',
        'current_owner_id',
        'current_owner_type',
        'attributes',
    ];

    protected $casts = [
        'cost' => 'decimal:4',
        'attributes' => 'array',
    ];

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_SOLD = 'sold';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_SCRAPPED = 'scrapped';

    /**
     * Get the lot this serial belongs to
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    /**
     * Check if serial is available for sale
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    /**
     * Check if serial is reserved
     */
    public function isReserved(): bool
    {
        return $this->status === self::STATUS_RESERVED;
    }

    /**
     * Check if serial has been sold
     */
    public function isSold(): bool
    {
        return $this->status === self::STATUS_SOLD;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at->toDateTime());
    }
}
