<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Connector credential model for storing encrypted service credentials.
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string $service_name
 * @property string $auth_method
 * @property array $credential_data
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property string|null $refresh_token
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ConnectorCredential extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id',
        'service_name',
        'auth_method',
        'credential_data',
        'expires_at',
        'refresh_token',
        'is_active',
    ];

    protected $casts = [
        'credential_data' => 'encrypted:array',
        'refresh_token' => 'encrypted',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Check if credentials have expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Scope to filter active credentials only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by service and tenant.
     */
    public function scopeForService($query, string $serviceName, ?string $tenantId = null)
    {
        $query->where('service_name', $serviceName);

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }
}
