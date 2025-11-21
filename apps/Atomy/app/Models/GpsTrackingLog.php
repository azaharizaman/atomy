<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\FieldService\ValueObjects\GpsLocation;

/**
 * GPS Tracking Log Eloquent Model
 *
 * @property string $id
 * @property string $work_order_id
 * @property string $technician_id
 * @property float $latitude
 * @property float $longitude
 * @property float|null $accuracy
 * @property \DateTimeInterface $captured_at
 */
class GpsTrackingLog extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'work_order_id',
        'technician_id',
        'latitude',
        'longitude',
        'accuracy',
        'captured_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'decimal:2',
        'captured_at' => 'datetime',
    ];

    // Business Logic

    public function toValueObject(): GpsLocation
    {
        return GpsLocation::fromCoordinates(
            (float) $this->latitude,
            (float) $this->longitude
        );
    }

    public function distanceTo(GpsLocation $other): float
    {
        return $this->toValueObject()->distanceTo($other);
    }

    // Eloquent Relationships

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'technician_id');
    }

    // Scopes

    public function scopeForWorkOrder($query, string $workOrderId)
    {
        return $query->where('work_order_id', $workOrderId);
    }

    public function scopeForTechnician($query, string $technicianId)
    {
        return $query->where('technician_id', $technicianId);
    }

    public function scopeCapturedBetween($query, \DateTimeInterface $start, \DateTimeInterface $end)
    {
        return $query->whereBetween('captured_at', [$start, $end]);
    }

    public function scopeOlderThan($query, int $days)
    {
        $threshold = now()->subDays($days);
        return $query->where('captured_at', '<', $threshold);
    }
}
