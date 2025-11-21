<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Geographic regions with polygon boundaries.
 * 
 * @property string $id
 * @property string $tenant_id
 * @property string $code
 * @property string $name
 * @property array $boundary_polygon
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 */
final class GeoRegion extends Model
{
    protected $table = 'geo_regions';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'boundary_polygon',
    ];

    protected $casts = [
        'boundary_polygon' => 'array',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
