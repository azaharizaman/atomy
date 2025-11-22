<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Nexus\Tenant\Contracts\TenantContextInterface;

/**
 * Tenant Scope
 *
 * Automatically filters queries by the current tenant context.
 * Apply this scope to models that should be tenant-isolated.
 */
class TenantScope implements Scope
{
    public function __construct(
        private readonly TenantContextInterface $tenantContext
    ) {
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if ($this->tenantContext->hasTenant()) {
            $builder->where($model->getTable() . '.tenant_id', $this->tenantContext->getCurrentTenantId());
        }
    }

    /**
     * Extend the query builder with tenant-specific methods.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenantScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('forTenant', function (Builder $builder, string $tenantId) {
            $model = $builder->getModel();
            return $builder->withoutGlobalScope($this)
                ->where($model->getTable() . '.tenant_id', $tenantId);
        });
    }
}
