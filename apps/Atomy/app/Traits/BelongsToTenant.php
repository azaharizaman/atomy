<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use App\Scopes\TenantScope;

/**
 * BelongsToTenant Trait
 *
 * Apply this trait to any model that should be automatically scoped to the current tenant.
 * Models with this trait will have tenant_id filtering applied automatically.
 *
 * Usage:
 * ```php
 * use App\Traits\BelongsToTenant;
 *
 * class Invoice extends Model
 * {
 *     use BelongsToTenant;
 * }
 * ```
 */
trait BelongsToTenant
{
    /**
     * Boot the trait and apply the global scope
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    /**
     * Get the tenant relationship
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
