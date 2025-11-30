<?php

declare(strict_types=1);

namespace Nexus\Laravel\Inventory\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Inventory\Enums\MovementType;

/**
 * Eloquent model for Stock Movements
 * 
 * @property string $id
 * @property string $product_id
 * @property string $warehouse_id
 * @property string $movement_type
 * @property float $quantity
 * @property float $unit_cost
 * @property float $total_cost
 * @property string|null $reference_id
 * @property string|null $reference_type
 * @property string|null $lot_id
 * @property string|null $serial_number
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class StockMovement extends Model
{
    use HasUlids;

    protected $table = 'inv_stock_movements';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'movement_type',
        'quantity',
        'unit_cost',
        'total_cost',
        'reference_id',
        'reference_type',
        'lot_id',
        'serial_number',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    /**
     * Get the stock level this movement affects
     */
    public function stockLevel(): BelongsTo
    {
        return $this->belongsTo(StockLevel::class, 'product_id', 'product_id')
            ->where('warehouse_id', $this->warehouse_id);
    }

    /**
     * Get the lot if applicable
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    /**
     * Get movement type as enum
     */
    public function getMovementTypeEnum(): MovementType
    {
        return MovementType::from($this->movement_type);
    }

    /**
     * Check if this is an inbound movement
     */
    public function isInbound(): bool
    {
        return in_array($this->movement_type, [
            MovementType::RECEIPT->value,
            MovementType::TRANSFER_IN->value,
            MovementType::RESERVATION_RELEASE->value,
        ], true);
    }

    /**
     * Check if this is an outbound movement
     */
    public function isOutbound(): bool
    {
        return in_array($this->movement_type, [
            MovementType::ISSUE->value,
            MovementType::TRANSFER_OUT->value,
            MovementType::RESERVATION->value,
        ], true);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at->toDateTime());
    }
}
