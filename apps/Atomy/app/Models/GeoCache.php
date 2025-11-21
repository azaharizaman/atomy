<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Geocoding cache for address-to-coordinates conversions.
 * 
 * @property string $id
 * @property string $tenant_id
 * @property string $address
 * @property float $latitude
 * @property float $longitude
 * @property string $provider
 * @property array|null $metadata
 * @property \DateTimeImmutable $cached_at
 * @property \DateTimeImmutable $expires_at
 */
final class GeoCache extends Model
{
    public $timestamps = false;

    protected $table = 'geo_cache';

    protected $fillable = [
        'tenant_id',
        'address',
        'latitude',
        'longitude',
        'provider',
        'metadata',
        'cached_at',
        'expires_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'metadata' => 'array',
        'cached_at' => 'immutable_datetime',
        'expires_at' => 'immutable_datetime',
    ];

    protected $attributes = [
        'metadata' => '{}',
    ];
}
