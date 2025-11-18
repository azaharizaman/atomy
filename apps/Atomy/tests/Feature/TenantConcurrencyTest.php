<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Jobs\TenantContextTestJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Tenant\Contracts\TenantContextInterface;

/**
 * Concurrency and stress tests for tenant context propagation.
 */
class TenantConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private TenantContextInterface $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = app(TenantContextInterface::class);
    }

    /**
     * Test multiple jobs with different tenant contexts process correctly.
     */
    public function test_multiple_jobs_with_different_tenants_maintain_isolation(): void
    {
        // Create 3 tenants
        $tenants = collect([
            Tenant::create(['name' => 'Tenant A', 'subdomain' => 'tenant-a', 'status' => 'active']),
            Tenant::create(['name' => 'Tenant B', 'subdomain' => 'tenant-b', 'status' => 'active']),
            Tenant::create(['name' => 'Tenant C', 'subdomain' => 'tenant-c', 'status' => 'active']),
        ]);

        $processedJobs = [];

        // Dispatch 10 jobs for each tenant (30 total)
        foreach ($tenants as $tenant) {
            for ($i = 0; $i < 10; $i++) {
                $this->tenantContext->setTenant($tenant->id);
                $job = new TenantContextTestJob();
                
                // Store tenant ID for verification
                $processedJobs[] = [
                    'job' => $job,
                    'expected_tenant_id' => $tenant->id,
                ];
            }
        }

        // Process all jobs and verify each maintained correct tenant context
        foreach ($processedJobs as $item) {
            $job = $item['job'];
            $expectedTenantId = $item['expected_tenant_id'];
            
            // Clear context to simulate queue worker
            $this->tenantContext->clearTenant();
            
            // Process with middleware
            $middleware = app(\App\Jobs\Middleware\SetTenantContext::class);
            $middleware->handle($job, function($job) {
                $job->handle($this->tenantContext);
            });
            
            // Verify correct tenant was restored
            $this->assertEquals(
                $expectedTenantId,
                TenantContextTestJob::$processedTenantId,
                "Job did not process with correct tenant context"
            );
        }
    }

    /**
     * Test that tenant context doesn't leak between jobs.
     */
    public function test_tenant_context_does_not_leak_between_jobs(): void
    {
        $tenantA = Tenant::create(['name' => 'Tenant A', 'subdomain' => 'tenant-a', 'status' => 'active']);
        $tenantB = Tenant::create(['name' => 'Tenant B', 'subdomain' => 'tenant-b', 'status' => 'active']);

        // Create jobs for different tenants
        $this->tenantContext->setTenant($tenantA->id);
        $jobA = new TenantContextTestJob();

        $this->tenantContext->setTenant($tenantB->id);
        $jobB = new TenantContextTestJob();

        // Clear context
        $this->tenantContext->clearTenant();
        $middleware = app(\App\Jobs\Middleware\SetTenantContext::class);

        // Process job A
        $middleware->handle($jobA, function($job) {
            $job->handle($this->tenantContext);
        });
        $resultA = TenantContextTestJob::$processedTenantId;

        // Verify context was cleared after job A
        $this->assertFalse($this->tenantContext->hasTenant(), "Context not cleared after job A");

        // Process job B
        $middleware->handle($jobB, function($job) {
            $job->handle($this->tenantContext);
        });
        $resultB = TenantContextTestJob::$processedTenantId;

        // Verify context was cleared after job B
        $this->assertFalse($this->tenantContext->hasTenant(), "Context not cleared after job B");

        // Verify each job processed with correct tenant
        $this->assertEquals($tenantA->id, $resultA, "Job A processed with wrong tenant");
        $this->assertEquals($tenantB->id, $resultB, "Job B processed with wrong tenant");
        $this->assertNotEquals($resultA, $resultB, "Jobs leaked context between executions");
    }

    /**
     * Test rapid succession of job dispatches maintains tenant isolation.
     */
    public function test_rapid_job_dispatches_maintain_tenant_isolation(): void
    {
        $tenants = collect([
            Tenant::create(['name' => 'Tenant 1', 'subdomain' => 'tenant-1', 'status' => 'active']),
            Tenant::create(['name' => 'Tenant 2', 'subdomain' => 'tenant-2', 'status' => 'active']),
        ]);

        $jobs = [];

        // Rapidly dispatch 100 jobs alternating between tenants
        for ($i = 0; $i < 100; $i++) {
            $tenant = $tenants[$i % 2];
            $this->tenantContext->setTenant($tenant->id);
            
            $job = new TenantContextTestJob();
            $jobs[] = [
                'job' => $job,
                'expected_tenant_id' => $tenant->id,
                'iteration' => $i,
            ];
        }

        // Process all jobs and verify isolation
        $failures = [];
        foreach ($jobs as $item) {
            $this->tenantContext->clearTenant();
            
            $middleware = app(\App\Jobs\Middleware\SetTenantContext::class);
            $middleware->handle($item['job'], function($job) {
                $job->handle($this->tenantContext);
            });
            
            if (TenantContextTestJob::$processedTenantId !== $item['expected_tenant_id']) {
                $failures[] = "Iteration {$item['iteration']}: Expected {$item['expected_tenant_id']}, got " . TenantContextTestJob::$processedTenantId;
            }
        }

        $this->assertEmpty($failures, "Tenant isolation failures:\n" . implode("\n", $failures));
    }

    /**
     * Test that null tenant jobs don't interfere with tenant-aware jobs.
     */
    public function test_null_tenant_jobs_do_not_interfere_with_tenant_jobs(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant A', 'subdomain' => 'tenant-a', 'status' => 'active']);

        // Create job with tenant
        $this->tenantContext->setTenant($tenant->id);
        $jobWithTenant = new TenantContextTestJob();

        // Create job without tenant
        $this->tenantContext->clearTenant();
        $jobWithoutTenant = new TenantContextTestJob();

        // Verify tenant IDs captured correctly
        $this->assertEquals($tenant->id, $jobWithTenant->tenantId);
        $this->assertNull($jobWithoutTenant->tenantId);

        // Process job without tenant first
        $middleware = app(\App\Jobs\Middleware\SetTenantContext::class);
        
        $middleware->handle($jobWithoutTenant, function($job) {
            $job->handle($this->tenantContext);
        });
        $this->assertNull(TenantContextTestJob::$processedTenantId);
        $this->assertFalse($this->tenantContext->hasTenant());

        // Process job with tenant
        $middleware->handle($jobWithTenant, function($job) {
            $job->handle($this->tenantContext);
        });
        $this->assertEquals($tenant->id, TenantContextTestJob::$processedTenantId);
        $this->assertFalse($this->tenantContext->hasTenant(), "Context not cleared after job");
    }
}
