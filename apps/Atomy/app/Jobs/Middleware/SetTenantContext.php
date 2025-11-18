<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use Nexus\Tenant\Contracts\TenantContextInterface;

/**
 * Queue job middleware to preserve and restore tenant context.
 *
 * This middleware ensures that queued jobs execute within the same tenant
 * context from which they were dispatched.
 */
final readonly class SetTenantContext
{
    public function __construct(
        private TenantContextInterface $tenantContext
    ) {}

    /**
     * Process the queued job with tenant context.
     *
     * @param mixed $job
     * @param callable $next
     * @return mixed
     */
    public function handle(mixed $job, callable $next): mixed
    {
        // Check if job has tenant context
        if (!property_exists($job, 'tenantId') || $job->tenantId === null) {
            // No tenant context to restore, proceed without setting
            return $next($job);
        }

        // Set tenant context before processing job
        $this->tenantContext->setTenant($job->tenantId);

        try {
            // Execute the job
            return $next($job);
        } finally {
            // Clear tenant context after job completion
            $this->tenantContext->clearTenant();
        }
    }
}
