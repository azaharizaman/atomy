<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nexus\Tenant\Contracts\TenantContextInterface;
use Tests\Feature\Jobs\TenantContextTestJob;
use Tests\TestCase;

/**
 * Performance benchmarks for tenant operations.
 * 
 * Target: Tenant resolution <1ms, Context switching <5ms
 */
class TenantPerformanceBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    private TenantContextInterface $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = app(TenantContextInterface::class);
    }

    /**
     * Benchmark: Tenant context setting performance.
     * Target: <1ms per operation
     */
    public function test_tenant_context_setting_performance(): void
    {
        $tenant = Tenant::create([
            'name' => 'Performance Test Tenant',
            'subdomain' => 'perf-test',
            'status' => 'active',
        ]);

        $iterations = 1000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->tenantContext->setTenant($tenant->id);
            $this->tenantContext->clearTenant();
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $avgTime = $totalTime / $iterations;

        $this->assertLessThan(
            1.0,
            $avgTime,
            sprintf(
                "Tenant context setting took %.4fms on average (target: <1ms). Total: %.2fms for %d iterations",
                $avgTime,
                $totalTime,
                $iterations
            )
        );

        echo sprintf(
            "\n✓ Tenant Context Setting: %.4fms average (%.2fms total for %d iterations)\n",
            $avgTime,
            $totalTime,
            $iterations
        );
    }

    /**
     * Benchmark: Tenant context retrieval performance.
     * Target: <1ms per operation
     */
    public function test_tenant_context_retrieval_performance(): void
    {
        $tenant = Tenant::create([
            'name' => 'Performance Test Tenant',
            'subdomain' => 'perf-test',
            'status' => 'active',
        ]);

        $this->tenantContext->setTenant($tenant->id);

        $iterations = 10000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->tenantContext->getCurrentTenantId();
            $this->tenantContext->hasTenant();
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = $totalTime / $iterations;

        $this->assertLessThan(
            1.0,
            $avgTime,
            sprintf(
                "Tenant context retrieval took %.4fms on average (target: <1ms)",
                $avgTime
            )
        );

        echo sprintf(
            "\n✓ Tenant Context Retrieval: %.4fms average (%.2fms total for %d iterations)\n",
            $avgTime,
            $totalTime,
            $iterations
        );
    }

    /**
     * Benchmark: Queue job serialization with tenant context.
     * Target: <5ms per job
     */
    public function test_queue_job_serialization_performance(): void
    {
        $tenant = Tenant::create([
            'name' => 'Performance Test Tenant',
            'subdomain' => 'perf-test',
            'status' => 'active',
        ]);

        $this->tenantContext->setTenant($tenant->id);

        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $job = new TenantContextTestJob();
            // Simulate serialization (what happens when job is dispatched)
            $serialized = serialize($job);
            $unserialized = unserialize($serialized);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = $totalTime / $iterations;

        $this->assertLessThan(
            5.0,
            $avgTime,
            sprintf(
                "Queue job serialization took %.4fms on average (target: <5ms)",
                $avgTime
            )
        );

        echo sprintf(
            "\n✓ Queue Job Serialization: %.4fms average (%.2fms total for %d iterations)\n",
            $avgTime,
            $totalTime,
            $iterations
        );
    }

    /**
     * Benchmark: Complete job lifecycle with middleware.
     * Target: <10ms per job
     */
    public function test_complete_job_lifecycle_performance(): void
    {
        $tenant = Tenant::create([
            'name' => 'Performance Test Tenant',
            'subdomain' => 'perf-test',
            'status' => 'active',
        ]);

        $middleware = app(\App\Jobs\Middleware\SetTenantContext::class);
        $iterations = 100;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            // Set tenant and create job
            $this->tenantContext->setTenant($tenant->id);
            $job = new TenantContextTestJob();
            
            // Clear context (simulating queue worker)
            $this->tenantContext->clearTenant();
            
            // Process with middleware
            $middleware->handle($job, function($job) {
                $job->handle($this->tenantContext);
            });
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = $totalTime / $iterations;

        $this->assertLessThan(
            10.0,
            $avgTime,
            sprintf(
                "Complete job lifecycle took %.4fms on average (target: <10ms)",
                $avgTime
            )
        );

        echo sprintf(
            "\n✓ Complete Job Lifecycle: %.4fms average (%.2fms total for %d iterations)\n",
            $avgTime,
            $totalTime,
            $iterations
        );
    }

    /**
     * Benchmark: Tenant switching overhead.
     * Target: <2ms per switch
     */
    public function test_tenant_switching_overhead(): void
    {
        $tenants = collect([
            Tenant::create(['name' => 'Tenant 1', 'subdomain' => 'tenant-1', 'status' => 'active']),
            Tenant::create(['name' => 'Tenant 2', 'subdomain' => 'tenant-2', 'status' => 'active']),
            Tenant::create(['name' => 'Tenant 3', 'subdomain' => 'tenant-3', 'status' => 'active']),
        ]);

        $iterations = 1000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $tenant = $tenants[$i % 3];
            $this->tenantContext->setTenant($tenant->id);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = $totalTime / $iterations;

        $this->assertLessThan(
            2.0,
            $avgTime,
            sprintf(
                "Tenant switching took %.4fms on average (target: <2ms)",
                $avgTime
            )
        );

        echo sprintf(
            "\n✓ Tenant Switching: %.4fms average (%.2fms total for %d iterations)\n",
            $avgTime,
            $totalTime,
            $iterations
        );
    }
}
