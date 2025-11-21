<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Route optimization cache with gzip compression.
 * 
 * @property string $id
 * @property string $tenant_id
 * @property string $cache_key
 * @property string $compressed_route Binary gzipped route data
 * @property int $size_bytes Original uncompressed size
 * @property int $compressed_size_bytes Compressed size
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $expires_at
 */
final class RouteCache extends Model
{
    public $timestamps = false;

    protected $table = 'route_cache';

    protected $fillable = [
        'tenant_id',
        'cache_key',
        'compressed_route',
        'size_bytes',
        'compressed_size_bytes',
        'created_at',
        'expires_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'compressed_size_bytes' => 'integer',
        'created_at' => 'immutable_datetime',
        'expires_at' => 'immutable_datetime',
    ];
}
