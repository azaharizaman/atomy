<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Services;

use Nexus\ProcurementOperations\DTOs\SOX\SOXPerformanceMetrics;
use Nexus\ProcurementOperations\Enums\SOXControlPoint;
use Nexus\ProcurementOperations\Services\SOXMetricsStorageInterface;
use Nexus\ProcurementOperations\Services\SOXPerformanceMonitor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(SOXPerformanceMonitor::class)]
final class SOXPerformanceMonitorTest extends TestCase
{
    private SOXPerformanceMonitor $monitor;
    private MockObject&SOXMetricsStorageInterface $storage;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(SOXMetricsStorageInterface::class);

        $this->monitor = new SOXPerformanceMonitor(
            storage: $this->storage,
            logger: new NullLogger(),
        );
    }

    #[Test]
    public function recordValidation_stores_validation_metrics(): void
    {
        $tenantId = 'tenant-123';
        $controlPoint = SOXControlPoint::REQ_BUDGET_CHECK;

        $this->storage
            ->expects($this->once())
            ->method('storeValidation')
            ->with(
                $tenantId,
                $controlPoint,
                true,
                $this->greaterThan(0),
                $this->isNull(),
            );

        $this->monitor->recordValidation(
            tenantId: $tenantId,
            controlPoint: $controlPoint,
            passed: true,
            durationMs: 50.5,
        );
    }

    #[Test]
    public function recordTimeout_stores_timeout_event(): void
    {
        $tenantId = 'tenant-123';
        $controlPoint = SOXControlPoint::REQ_BUDGET_CHECK;

        $this->storage
            ->expects($this->once())
            ->method('storeTimeout')
            ->with($tenantId, $controlPoint, 200.0);

        $this->monitor->recordTimeout(
            tenantId: $tenantId,
            controlPoint: $controlPoint,
            durationMs: 200.0,
        );
    }

    #[Test]
    public function recordError_stores_error_event(): void
    {
        $tenantId = 'tenant-123';
        $controlPoint = SOXControlPoint::REQ_BUDGET_CHECK;
        $errorMessage = 'Database connection failed';

        $this->storage
            ->expects($this->once())
            ->method('storeError')
            ->with($tenantId, $controlPoint, $errorMessage);

        $this->monitor->recordError(
            tenantId: $tenantId,
            controlPoint: $controlPoint,
            errorMessage: $errorMessage,
        );
    }

    #[Test]
    public function getMetrics_returns_performance_metrics(): void
    {
        $tenantId = 'tenant-123';

        $this->storage
            ->method('getMetrics')
            ->with($tenantId, $this->anything())
            ->willReturn([
                'total_validations' => 100,
                'successful_validations' => 95,
                'failed_validations' => 5,
                'timeouts' => 2,
                'errors' => 1,
                'avg_duration_ms' => 45.5,
                'p50_duration_ms' => 40.0,
                'p95_duration_ms' => 85.0,
                'p99_duration_ms' => 150.0,
                'control_metrics' => [],
            ]);

        $metrics = $this->monitor->getMetrics($tenantId);

        $this->assertInstanceOf(SOXPerformanceMetrics::class, $metrics);
        $this->assertEquals(100, $metrics->totalValidations);
        $this->assertEquals(95, $metrics->successfulValidations);
        $this->assertEquals(2, $metrics->timeouts);
        $this->assertEquals(45.5, $metrics->avgDurationMs);
    }

    #[Test]
    public function getP95Latency_returns_percentile_for_control(): void
    {
        $tenantId = 'tenant-123';
        $controlPoint = SOXControlPoint::REQ_BUDGET_CHECK;

        $this->storage
            ->method('getPercentileLatency')
            ->with($tenantId, $controlPoint, 95)
            ->willReturn(85.5);

        $latency = $this->monitor->getP95Latency($tenantId, $controlPoint);

        $this->assertEquals(85.5, $latency);
    }

    #[Test]
    public function assessOptOutEligibility_returns_eligible_for_low_latency(): void
    {
        $tenantId = 'tenant-123';

        $this->storage
            ->method('getMetrics')
            ->willReturn([
                'total_validations' => 1000,
                'successful_validations' => 995,
                'failed_validations' => 5,
                'timeouts' => 0,
                'errors' => 0,
                'avg_duration_ms' => 30.0,
                'p50_duration_ms' => 25.0,
                'p95_duration_ms' => 50.0,
                'p99_duration_ms' => 80.0,
                'control_metrics' => [],
            ]);

        $result = $this->monitor->assessOptOutEligibility($tenantId);

        // With low latency and high success rate, should be eligible
        $this->assertIsBool($result['eligible']);
    }

    #[Test]
    public function assessOptOutEligibility_returns_ineligible_for_high_failure_rate(): void
    {
        $tenantId = 'tenant-123';

        $this->storage
            ->method('getMetrics')
            ->willReturn([
                'total_validations' => 100,
                'successful_validations' => 70,
                'failed_validations' => 30,
                'timeouts' => 5,
                'errors' => 3,
                'avg_duration_ms' => 150.0,
                'p50_duration_ms' => 100.0,
                'p95_duration_ms' => 300.0,
                'p99_duration_ms' => 500.0,
                'control_metrics' => [],
            ]);

        $result = $this->monitor->assessOptOutEligibility($tenantId);

        $this->assertFalse($result['eligible']);
        $this->assertNotEmpty($result['reasons']);
    }

    #[Test]
    public function getTenantsWithPerformanceIssues_returns_problem_tenants(): void
    {
        $this->storage
            ->method('getTenantsWithHighLatency')
            ->with(100.0)
            ->willReturn(['tenant-456', 'tenant-789']);

        $this->storage
            ->method('getTenantsWithHighErrorRate')
            ->with(0.05)
            ->willReturn(['tenant-789', 'tenant-101']);

        $problematic = $this->monitor->getTenantsWithPerformanceIssues(
            latencyThresholdMs: 100.0,
            errorRateThreshold: 0.05,
        );

        $this->assertContains('tenant-456', $problematic);
        $this->assertContains('tenant-789', $problematic);
        $this->assertContains('tenant-101', $problematic);
    }

    #[Test]
    public function getOptOutRecommendations_returns_recommendations(): void
    {
        $tenantId = 'tenant-123';

        $this->storage
            ->method('getMetrics')
            ->willReturn([
                'total_validations' => 5000,
                'successful_validations' => 4990,
                'failed_validations' => 10,
                'timeouts' => 0,
                'errors' => 0,
                'avg_duration_ms' => 20.0,
                'p50_duration_ms' => 15.0,
                'p95_duration_ms' => 35.0,
                'p99_duration_ms' => 50.0,
                'control_metrics' => [
                    SOXControlPoint::REQ_BUDGET_CHECK->value => [
                        'total' => 1000,
                        'success_rate' => 0.999,
                        'avg_duration_ms' => 15.0,
                    ],
                ],
            ]);

        $recommendations = $this->monitor->getOptOutRecommendations($tenantId);

        $this->assertIsArray($recommendations);
    }

    #[Test]
    public function clearMetrics_clears_tenant_metrics(): void
    {
        $tenantId = 'tenant-123';

        $this->storage
            ->expects($this->once())
            ->method('clearMetrics')
            ->with($tenantId);

        $this->monitor->clearMetrics($tenantId);
    }
}
