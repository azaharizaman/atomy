<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\FieldService\Contracts\PartsConsumptionInterface;

/**
 * Parts Consumption Eloquent Model
 *
 * @property string $id
 * @property string $work_order_id
 * @property string $product_variant_id
 * @property float $quantity
 * @property string $uom
 * @property string $source_warehouse_id
 * @property array|null $metadata
 */
class PartsConsumption extends Model implements PartsConsumptionInterface
{
    use HasUlids;

    protected $table = 'parts_consumption';

    protected $fillable = [
        'work_order_id',
        'product_variant_id',
        'quantity',
        'uom',
        'source_warehouse_id',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // PartsConsumptionInterface implementation

    public function getWorkOrderId(): string
    {
        return $this->work_order_id;
    }

    public function getProductVariantId(): string
    {
        return $this->product_variant_id;
    }

    public function getQuantity(): float
    {
        return (float) $this->quantity;
    }

    public function getUom(): string
    {
        return $this->uom;
    }

    public function getSourceWarehouseId(): string
    {
        return $this->source_warehouse_id;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    public function getConsumedAt(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable($this->created_at);
    }

    // Eloquent Relationships

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    // Scopes

    public function scopeForWorkOrder($query, string $workOrderId)
    {
        return $query->where('work_order_id', $workOrderId);
    }

    public function scopeFromVan($query)
    {
        return $query->where('source_warehouse_id', 'LIKE', 'VAN-%');
    }

    public function scopeFromWarehouse($query)
    {
        return $query->where('source_warehouse_id', 'NOT LIKE', 'VAN-%');
    }
}
