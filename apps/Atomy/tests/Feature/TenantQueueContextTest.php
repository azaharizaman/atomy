<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Tests\Feature\Jobs\TenantContextTestJob;
use Tests\TestCase;

/**
 * Test suite for tenant context propagation in queued jobs.
 */
class TenantQueueContextTest extends TestCase
{
    use RefreshDatabase;

    private TenantContextInterface $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = app(TenantContextInterface::class);
    }

    /**
     * Test that tenant context is preserved when dispatching a job.
     *
     * Requirement: ARC-TEN-0587
     */
    public function test_tenant_context_is_serialized_with_job(): void
    {
        // Create a tenant
        $tenant = Tenant::create([
            'name' => 'Test Tenant A',
            'subdomain' => 'tenant-a',
            'status' => 'active',
        ]);

        // Set tenant context
        $this->tenantContext->setTenant($tenant->id);

        // Dispatch job
        $job = new TenantContextTestJob();
        
        // Assert tenant ID was captured
        $this->assertEquals($tenant->id, $job->tenantId);
    }

    /**
     * Test that tenant context is restored when job is processed.
     *
     * Requirement: ARC-TEN-0587
     */
    public function test_tenant_context_is_restored_when_job_processes(): void
    {
        // Create two tenants
        $tenantA = Tenant::create([
            'name' => 'Test Tenant A',
            'subdomain' => 'tenant-a',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Test Tenant B',
            'subdomain' => 'tenant-b',
            'status' => 'active',
        ]);

        // Set tenant A context and dispatch job
        $this->tenantContext->setTenant($tenantA->id);
        $job = new TenantContextTestJob();
        
        // Change to tenant B context (simulating different request)
        $this->tenantContext->setTenant($tenantB->id);

        // Process the job synchronously
        $job->handle($this->tenantContext);

        // Verify the job processed with tenant A context (not B)
        $this->assertEquals($tenantA->id, TenantContextTestJob::$processedTenantId);
    }

    /**
     * Test that jobs without tenant context don't fail.
     */
    public function test_jobs_without_tenant_context_work_normally(): void
    {
        // Ensure no tenant context is set
        $this->tenantContext->clearTenant();

        // Dispatch job
        $job = new TenantContextTestJob();

        // Assert no tenant ID was captured
        $this->assertNull($job->tenantId);

        // Job should still process without error
        $job->handle($this->tenantContext);
        
        $this->assertNull(TenantContextTestJob::$processedTenantId);
    }

    /**
     * Test that tenant context is cleared after job completion.
     */
    public function test_tenant_context_is_cleared_after_job_completion(): void
    {
        // Create a tenant
        $tenant = Tenant::create([
            'name' => 'Test Tenant A',
            'subdomain' => 'tenant-a',
            'status' => 'active',
        ]);

        // Set tenant context
        $this->tenantContext->setTenant($tenant->id);
        $job = new TenantContextTestJob();

        // Clear context before processing (simulating queue worker)
        $this->tenantContext->clearTenant();
        
        // Process job (middleware should restore and then clear)
        $middleware = app(\App\Jobs\Middleware\SetTenantContext::class);
        $middleware->handle($job, function($job) {
            $job->handle($this->tenantContext);
        });

        // Context should be cleared after processing
        $this->assertFalse($this->tenantContext->hasTenant());
    }
}
