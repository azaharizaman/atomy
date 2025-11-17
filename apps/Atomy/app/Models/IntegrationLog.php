<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Integration log model for storing external API call history.
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string $service_name
 * @property string $endpoint
 * @property string $method
 * @property string $status
 * @property int|null $http_status_code
 * @property int $duration_ms
 * @property array|null $request_data
 * @property array|null $response_data
 * @property string|null $error_message
 * @property int $attempt_number
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class IntegrationLog extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'service_name',
        'endpoint',
        'method',
        'status',
        'http_status_code',
        'duration_ms',
        'request_data',
        'response_data',
        'error_message',
        'attempt_number',
    ];

    protected $casts = [
        'http_status_code' => 'integer',
        'duration_ms' => 'integer',
        'request_data' => 'array',
        'response_data' => 'array',
        'attempt_number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to filter by service name.
     */
    public function scopeForService($query, string $serviceName)
    {
        return $query->where('service_name', $serviceName);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, \DateTimeInterface $from, \DateTimeInterface $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeForTenant($query, ?string $tenantId)
    {
        if ($tenantId === null) {
            return $query->whereNull('tenant_id');
        }

        return $query->where('tenant_id', $tenantId);
    }
}
