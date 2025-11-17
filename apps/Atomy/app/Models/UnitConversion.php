<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Uom\Contracts\ConversionRuleInterface;

/**
 * Eloquent model for Unit Conversion rules.
 *
 * Implements the package ConversionRuleInterface for Laravel persistence.
 *
 * Requirements: ARC-UOM-0030, FR-UOM-102, FR-UOM-205, SEC-UOM-103
 *
 * @property string $id
 * @property string $from_unit_code
 * @property string $to_unit_code
 * @property float $ratio
 * @property float $offset
 * @property bool $is_bidirectional
 * @property int $version
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class UnitConversion extends Model implements ConversionRuleInterface
{
    use HasFactory, HasUlids, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'unit_conversions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'from_unit_code',
        'to_unit_code',
        'ratio',
        'offset',
        'is_bidirectional',
        'version',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ratio' => 'float',
        'offset' => 'float',
        'is_bidirectional' => 'boolean',
        'version' => 'integer',
    ];

    /**
     * Get the source unit.
     *
     * @return BelongsTo
     */
    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'from_unit_code', 'code');
    }

    /**
     * Get the target unit.
     *
     * @return BelongsTo
     */
    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'to_unit_code', 'code');
    }

    /**
     * {@inheritDoc}
     */
    public function getFromUnit(): string
    {
        return $this->from_unit_code;
    }

    /**
     * {@inheritDoc}
     */
    public function getToUnit(): string
    {
        return $this->to_unit_code;
    }

    /**
     * {@inheritDoc}
     */
    public function getRatio(): float
    {
        return $this->ratio;
    }

    /**
     * {@inheritDoc}
     */
    public function getOffset(): float
    {
        return $this->offset;
    }

    /**
     * {@inheritDoc}
     */
    public function hasOffset(): bool
    {
        return $this->offset !== 0.0;
    }

    /**
     * {@inheritDoc}
     */
    public function isBidirectional(): bool
    {
        return $this->is_bidirectional;
    }
}
