<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Uom\Contracts\DimensionInterface;

/**
 * Eloquent model for Dimension (measurement category).
 *
 * Implements the package DimensionInterface for Laravel persistence.
 *
 * Requirements: ARC-UOM-0030, FR-UOM-201
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $base_unit_code
 * @property bool $allows_offset
 * @property string|null $description
 * @property bool $is_system_defined
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Dimension extends Model implements DimensionInterface
{
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dimensions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'code',
        'name',
        'base_unit_code',
        'allows_offset',
        'description',
        'is_system_defined',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'allows_offset' => 'boolean',
        'is_system_defined' => 'boolean',
    ];

    /**
     * Get units belonging to this dimension.
     *
     * @return HasMany
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class, 'dimension_code', 'code');
    }

    /**
     * Get the base unit for this dimension.
     *
     * @return BelongsTo
     */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_code', 'code');
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
    public function getBaseUnit(): string
    {
        return $this->base_unit_code;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * {@inheritDoc}
     */
    public function allowsOffset(): bool
    {
        return $this->allows_offset;
    }
}
