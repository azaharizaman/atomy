<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Uom\Contracts\UnitInterface;

/**
 * Eloquent model for Unit of Measurement.
 *
 * Implements the package UnitInterface for Laravel persistence.
 *
 * Requirements: ARC-UOM-0030, BUS-UOM-105, BUS-UOM-204, SEC-UOM-101, SEC-UOM-102
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $symbol
 * @property string $dimension_code
 * @property string|null $system_code
 * @property bool $is_base_unit
 * @property bool $is_system_unit
 * @property string|null $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Unit extends Model implements UnitInterface
{
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'units';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'dimension_code',
        'system_code',
        'is_base_unit',
        'is_system_unit',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_base_unit' => 'boolean',
        'is_system_unit' => 'boolean',
    ];

    /**
     * Get the dimension this unit belongs to.
     *
     * @return BelongsTo
     */
    public function dimension(): BelongsTo
    {
        return $this->belongsTo(Dimension::class, 'dimension_code', 'code');
    }

    /**
     * Get the unit system this unit belongs to.
     *
     * @return BelongsTo
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(UnitSystem::class, 'system_code', 'code');
    }

    /**
     * Get conversions where this unit is the source.
     *
     * @return HasMany
     */
    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'from_unit_code', 'code');
    }

    /**
     * Get conversions where this unit is the target.
     *
     * @return HasMany
     */
    public function conversionsTo(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'to_unit_code', 'code');
    }

    /**
     * {@inheritDoc}
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /**
     * {@inheritDoc}
     */
    public function getDimension(): string
    {
        return $this->dimension_code;
    }

    /**
     * {@inheritDoc}
     */
    public function getSystem(): ?string
    {
        return $this->system_code;
    }

    /**
     * {@inheritDoc}
     */
    public function isBaseUnit(): bool
    {
        return $this->is_base_unit;
    }

    /**
     * {@inheritDoc}
     */
    public function isSystemUnit(): bool
    {
        return $this->is_system_unit;
    }
}
