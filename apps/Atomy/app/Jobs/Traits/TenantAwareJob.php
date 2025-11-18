<?php

declare(strict_types=1);

namespace App\Jobs\Traits;

use App\Jobs\Middleware\SetTenantContext;
use Nexus\Tenant\Contracts\TenantContextInterface;

/**
 * Trait for jobs that need tenant context preservation.
 *
 * Jobs using this trait will automatically:
 * 1. Serialize the current tenant ID when dispatched
 * 2. Restore the tenant context when processed
 * 3. Apply the SetTenantContext middleware
 */
trait TenantAwareJob
{
    /**
     * The tenant ID for this job.
     */
    public ?string $tenantId = null;

    /**
     * Capture the current tenant context when job is created.
     */
    public function __construct()
    {
        $tenantContext = app(TenantContextInterface::class);
        
        if ($tenantContext->hasTenant()) {
            $this->tenantId = $tenantContext->getCurrentTenantId();
        }
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            app(SetTenantContext::class),
        ];
    }
}
