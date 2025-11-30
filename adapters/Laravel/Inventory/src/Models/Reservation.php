<?php

declare(strict_types=1);

namespace Nexus\Laravel\Inventory\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Nexus\Inventory\Enums\ReservationStatus;

/**
 * Eloquent model for Stock Reservations
 * 
 * @property string $id
 * @property string $product_id
 * @property string $warehouse_id
 * @property float $quantity
 * @property string $reference_id
 * @property string $reference_type
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Reservation extends Model
{
    use HasUlids;

    protected $table = 'inv_reservations';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'reference_id',
        'reference_type',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'expires_at' => 'datetime',
    ];

    /**
     * Get status as enum
     */
    public function getStatusEnum(): ReservationStatus
    {
        return ReservationStatus::from($this->status);
    }

    /**
     * Check if reservation is active
     */
    public function isActive(): bool
    {
        if (!$this->getStatusEnum()->isActive()) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if reservation has expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        if ($this->expires_at === null) {
            return null;
        }
        return DateTimeImmutable::createFromMutable($this->expires_at->toDateTime());
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->created_at->toDateTime());
    }
}
