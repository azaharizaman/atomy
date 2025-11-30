<?php

declare(strict_types=1);

namespace Nexus\Laravel\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent model for Stock Levels
 * 
 * @property string $id
 * @property string $product_id
 * @property string $warehouse_id
 * @property float $quantity
 * @property float $reserved_quantity
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class StockLevel extends Model
{
    use HasUlids;

    protected $table = 'inv_stock_levels';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'reserved_quantity' => 'decimal:4',
    ];

    /**
     * Get stock movements for this stock level
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id', 'product_id')
            ->where('warehouse_id', $this->warehouse_id);
    }

    /**
     * Get available quantity (quantity minus reserved)
     */
    public function getAvailableQuantity(): float
    {
        return (float) bcsub((string) $this->quantity, (string) $this->reserved_quantity, 4);
    }
}
