<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Route optimization execution logs with constraint violations.
 * 
 * @property string $id
 * @property string $tenant_id
 * @property string $optimization_type (tsp|vrp)
 * @property int $stop_count
 * @property int $execution_time_ms
 * @property array $constraint_violations
 * @property array $metadata
 * @property \DateTimeImmutable $created_at
 */
final class RouteOptimizationLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'route_optimization_logs';

    protected $fillable = [
        'tenant_id',
        'optimization_type',
        'stop_count',
        'execution_time_ms',
        'constraint_violations',
        'metadata',
    ];

    protected $casts = [
        'stop_count' => 'integer',
        'execution_time_ms' => 'integer',
        'constraint_violations' => 'array',
        'metadata' => 'array',
        'created_at' => 'immutable_datetime',
    ];

    protected $attributes = [
        'constraint_violations' => '[]',
        'metadata' => '{}',
    ];
}
