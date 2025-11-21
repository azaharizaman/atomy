<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Warehouse\Contracts\BinLocationInterface;

final class BinLocation extends Model implements BinLocationInterface
{
    use HasUlids;

    protected $fillable = [
        'warehouse_id',
        'code',
        'aisle',
        'rack',
        'shelf',
        'coordinates_latitude',
        'coordinates_longitude',
        'is_active',
    ];

    protected $casts = [
        'coordinates_latitude' => 'float',
        'coordinates_longitude' => 'float',
        'is_active' => 'boolean',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    // BinLocationInterface implementation
    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getWarehouseId(): string
    {
        return $this->warehouse_id;
    }

    public function getAisle(): ?string
    {
        return $this->aisle;
    }

    public function getRack(): ?string
    {
        return $this->rack;
    }

    public function getShelf(): ?string
    {
        return $this->shelf;
    }

    public function getCoordinates(): ?array
    {
        if ($this->coordinates_latitude === null || $this->coordinates_longitude === null) {
            return null;
        }

        return [
            'latitude' => $this->coordinates_latitude,
            'longitude' => $this->coordinates_longitude,
        ];
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}
