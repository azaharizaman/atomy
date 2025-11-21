<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Work Order Photo Eloquent Model
 *
 * @property string $id
 * @property string $work_order_id
 * @property string $document_id
 * @property string $photo_type
 * @property string|null $caption
 * @property array|null $gps_location
 * @property \DateTimeInterface $captured_at
 */
class WorkOrderPhoto extends Model
{
    use HasUlids;

    protected $fillable = [
        'work_order_id',
        'document_id',
        'photo_type',
        'caption',
        'gps_location',
        'captured_at',
    ];

    protected $casts = [
        'gps_location' => 'array',
        'captured_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Eloquent Relationships

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    // Scopes

    public function scopeForWorkOrder($query, string $workOrderId)
    {
        return $query->where('work_order_id', $workOrderId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('photo_type', $type);
    }

    public function scopeBeforeService($query)
    {
        return $query->where('photo_type', 'before');
    }

    public function scopeAfterService($query)
    {
        return $query->where('photo_type', 'after');
    }
}
