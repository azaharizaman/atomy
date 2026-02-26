<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Telemetry\Contracts\HealthCheckInterface;
use Nexus\Telemetry\Contracts\ScheduledHealthCheckInterface;
use Nexus\Telemetry\Services\HealthCheckRunner;
use Nexus\Telemetry\ValueObjects\HealthCheckResult;
use Nexus\Telemetry\ValueObjects\HealthStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(HealthCheckRunner::class)]
#[Group('monitoring')]
#[Group('health-checks')]
final class HealthCheckRunnerTest extends TestCase
{
    private MockObject&LoggerInterface $logger;
    
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }
    
    #[Test]
    public function it_registers_and_retrieves_health_check(): void
    {
        $healthCheck = $this->createMock(HealthCheckInterface::class);
        $healthCheck->method('getName')->willReturn('database');
        
        $runner = new HealthCheckRunner($this->logger);
        $runner->registerCheck($healthCheck);
        
        $retrieved = $runner->getCheckByName('database');
        
        $this->assertSame($healthCheck, $retrieved);
    }
    
    #[Test]
    public function it_returns_null_for_non_existent_check(): void
    {
        $runner = new HealthCheckRunner($this->logger);
        
        $this->assertNull($runner->getCheckByName('non-existent'));
    }
    
    #[Test]
    public function it_runs_single_healthy_check(): void
    {
        $healthCheck = $this->createMock(HealthCheckInterface::class);
        $healthCheck->method('getName')->willReturn('cache');
        $healthCheck->method('isCritical')->willReturn(false);
        
        $result = new HealthCheckResult(
            checkName: 'cache',
            status: HealthStatus::HEALTHY,
            message: 'Cache is responding',
            responseTimeMs: 5.0,
            metadata: [],
            checkedAt: new DateTimeImmutable()
        );
        
        $healthCheck
            ->expects($this->once())
            ->method('check')
            ->willReturn($result);
        
        $runner = new HealthCheckRunner($this->logger);
        $runner->registerCheck($healthCheck);
        
        $results = $runner->runChecks();
        
        $this->assertCount(1, $results);
        $this->assertSame($result, $results['cache']);
    }
    
    #[Test]
    public function it_runs_multiple_health_checks(): void
    {
        $cacheCheck = $this->createMock(HealthCheckInterface::class);
        $cacheCheck->method('getName')->willReturn('cache');
        $cacheCheck->method('isCritical')->willReturn(false);
        $cacheCheck->method('check')->willReturn(
            new HealthCheckResult('cache', HealthStatus::HEALTHY, 'OK', 5.0, [], new DateTimeImmutable())
        );
        
        $dbCheck = $this->createMock(HealthCheckInterface::class);
        $dbCheck->method('getName')->willReturn('database');
        $dbCheck->method('isCritical')->willReturn(true);
        $dbCheck->method('check')->willReturn(
            new HealthCheckResult('database', HealthStatus::HEALTHY, 'OK', 12.0, [], new DateTimeImmutable())
        );
        
        $runner = new HealthCheckRunner($this->logger);
        $runner->registerCheck($cacheCheck);
        $runner->registerCheck($dbCheck);
        
        $results = $runner->runChecks();
        
        $this->assertCount(2, $results);
        $this->assertArrayHasKey('cache', $results);
        $this->assertArrayHasKey('database', $results);
    }
    
    #[Test]
    public function it_prioritizes_critical_checks_first(): void
    {
        $executionOrder = [];
        
        $normalCheck = $this->createMock(HealthCheckInterface::class);
        $normalCheck->method('getName')->willReturn('normal');
        $normalCheck->method('isCritical')->willReturn(false);
        $normalCheck->method('check')->willReturnCallback(function () use (&$executionOrder) {
            $executionOrder[] = 'normal';
            return new HealthCheckResult('normal', HealthStatus::HEALTHY, 'OK', 5.0, [], new DateTimeImmutable());
        });
        
        $criticalCheck = $this->createMock(HealthCheckInterface::class);
        $criticalCheck->method('getName')->willReturn('critical');
        $criticalCheck->method('isCritical')->willReturn(true);
        $criticalCheck->method('check')->willReturnCallback(function () use (&$executionOrder) {
            $executionOrder[] = 'critical';
            return new HealthCheckResult('critical', HealthStatus::HEALTHY, 'OK', 5.0, [], new DateTimeImmutable());
        });
        
        $runner = new HealthCheckRunner($this->logger);
        $runner->registerCheck($normalCheck); // Register normal first
        $runner->registerCheck($criticalCheck); // Register critical second
        
        $runner->runChecks();
        
        // Critical should run first despite being registered second
        $this->assertSame(['critical', 'normal'], $executionOrder);
    }
    
    #[Test]
    public function it_handles_check_exception_gracefully(): void
    {
        $failingCheck = $this->createMock(HealthCheckInterface::class);
        $failingCheck->method('getName')->willReturn('failing');
        $failingCheck->method('isCritical')->willReturn(false);
        $failingCheck
            ->method('check')
            ->willThrowException(new \RuntimeException('Connection timeout'));
        
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('Health check failed'),
                $this->callback(fn($context) => 
                    $context['check_name'] === 'failing' &&
                    str_contains($context['error'], 'Connection timeout')
                )
            );
        
        $runner = new HealthCheckRunner($this->logger);
        $runner->registerCheck($failingCheck);
        
        $results = $runner->runChecks();
        
        $this->assertCount(1, $results);
        $this->assertSame(HealthStatus::OFFLINE, $results['failing']->status);
        $this->assertStringContainsString('Connection timeout', $results['failing']->message);
    }
    
    #[Test]
    public function it_continues_running_other_checks_after_failure(): void
    {
        $failingCheck = $this->createMock(HealthCheckInterface::class);
        $failingCheck->method('getName')->willReturn('failing');
        $failingCheck->method('isCritical')->willReturn(false);
        $failingCheck->method('check')->willThrowException(new \Exception('Error'));
        
        $workingCheck = $this->createMock(HealthCheckInterface::class);
        $workingCheck->method('getName')->willReturn('working');
        $workingCheck->method('isCritical')->willReturn(false);
        $workingCheck->method('check')->willReturn(
            new HealthCheckResult('working', HealthStatus::HEALTHY, 'OK', 5.0, [], new DateTimeImmutable())
        );
        
        $runner = new HealthCheckRunner($this->logger);
        $runner->registerCheck($failingCheck);
        $runner->registerCheck($workingCheck);
        
        $results = $runner->runChecks();
        
        $this->assertCount(2, $results);
        $this->assertSame(HealthStatus::OFFLINE, $results['failing']->status);
        $this->assertSame(HealthStatus::HEALTHY, $results['working']->status);
    }
    
    #[Test]
    public function it_enforces_timeout_on_slow_checks(): void
    {
        $slowCheck = $this->createMock(HealthCheckInterface::class);
        $slowCheck->method('getName')->willReturn('slow');
        $slowCheck->method('isCritical')->willReturn(false);
        $slowCheck->method('check')->willReturnCallback(function () {
            sleep(3); // Simulate slow check
            return new HealthCheckResult('slow', HealthStatus::HEALTHY, 'OK', 3000.0, [], new DateTimeImmutable());
        });
        
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('Health check timed out'),
                $this->arrayHasKey('check_name')
            );
        
        $runner = new HealthCheckRunner($this->logger, defaultTimeoutSeconds: 1);
        $runner->registerCheck($slowCheck);
        
        $results = $runner->runChecks();
        
        $this->assertCount(1, $results);
        $this->assertSame(HealthStatus::DEGRADED, $results['slow']->status);
        $this->assertStringContainsString('timeout', strtolower($results['slow']->message));
    }
    
    #[Test]
    public function it_caches_results_when_caching_enabled(): void
    {
        $healthCheck = $this->createMock(HealthCheckInterface::class);
        $healthCheck->method('getName')->willReturn('cached');
        $healthCheck->method('isCritical')->willReturn(false);
        $healthCheck
            ->expects($this->once()) // Should only be called once due to caching
            ->method('check')
            ->willReturn(
                new HealthCheckResult('cached', HealthStatus::HEALTHY, 'OK', 5.0, [], new DateTimeImmutable())
            );
        
        $runner = new HealthCheckRunner($this->logger, enableCaching: true, cacheTtlSeconds: 60);
        $runner->registerCheck($healthCheck);
        
        // First run - should execute check
        $results1 = $runner->runChecks();
        
        // Second run - should use cached result
        $results2 = $runner->runChecks();
        
        $this->assertSame($results1['cached'], $results2['cached']);
    }
    
    #[Test]
    public function it_bypasses_cache_when_caching_disabled(): void
    {
        $healthCheck = $this->createMock(HealthCheckInterface::class);
        $healthCheck->method('getName')->willReturn('nocache');
        $healthCheck->method('isCritical')->willReturn(false);
        $healthCheck
            ->expects($this->exactly(2)) // Should be called twice
            ->method('check')
            ->willReturn(
                new HealthCheckResult('nocache', HealthStatus::HEALTHY, 'OK', 5.0, [], new DateTimeImmutable())
            );
        
        $runner = new HealthCheckRunner($this->logger, enableCaching: false);
        $runner->registerCheck($healthCheck);
        
        $runner->runChecks();
        $runner->runChecks();
    }
    
    #[Test]
    public function it_logs_successful_check_execution(): void
    {
        $healthCheck = $this->createMock(HealthCheckInterface::class);
        $healthCheck->method('getName')->willReturn('logging-test');
        $healthCheck->method('isCritical')->willReturn(false);
        $healthCheck->method('check')->willReturn(
            new HealthCheckResult('logging-test', HealthStatus::HEALTHY, 'OK', 5.0, [], new DateTimeImmutable())
        );
        
        $this->logger
            ->expects($this->exactly(2))
            ->method('debug')
            ->willReturnCallback(function (string $message, array $context) {
                static $callCount = 0;
                $callCount++;
                
                if ($callCount === 1) {
                    // First call: registration
                    $this->assertStringContainsString('registered', $message);
                } else {
                    // Second call: execution
                    $this->assertStringContainsString('executed', $message);
                    $this->assertSame('logging-test', $context['check_name']);
                    $this->assertSame('healthy', $context['status']);
                }
            });
        
        $runner = new HealthCheckRunner($this->logger);
        $runner->registerCheck($healthCheck);
        
        $runner->runChecks();
    }
    
    #[Test]
    public function it_prevents_duplicate_check_registration(): void
    {
        $check1 = $this->createMock(HealthCheckInterface::class);
        $check1->method('getName')->willReturn('duplicate');
        
        $check2 = $this->createMock(HealthCheckInterface::class);
        $check2->method('getName')->willReturn('duplicate');
        
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('already registered'));
        
        $runner = new HealthCheckRunner($this->logger);
        $runner->registerCheck($check1);
        $runner->registerCheck($check2); // Should warn and skip
        
        $retrieved = $runner->getCheckByName('duplicate');
        $this->assertSame($check1, $retrieved); // Should still be the first one
    }
    
    #[Test]
    public function it_clears_cache_on_demand(): void
    {
        $healthCheck = $this->createMock(HealthCheckInterface::class);
        $healthCheck->method('getName')->willReturn('cache-clear-test');
        $healthCheck->method('isCritical')->willReturn(false);
        $healthCheck
            ->expects($this->exactly(2))
            ->method('check')
            ->willReturn(
                new HealthCheckResult('cache-clear-test', HealthStatus::HEALTHY, 'OK', 5.0, [], new DateTimeImmutable())
            );
        
        $runner = new HealthCheckRunner($this->logger, enableCaching: true);
        $runner->registerCheck($healthCheck);
        
        $runner->runChecks(); // First run
        $runner->clearCache(); // Clear cache
        $runner->runChecks(); // Should execute again
    }
    
    #[Test]
    public function it_supports_scheduled_health_checks(): void
    {
        $scheduledCheck = $this->createMock(ScheduledHealthCheckInterface::class);
        $scheduledCheck->method('getName')->willReturn('scheduled');
        $scheduledCheck->method('isCritical')->willReturn(false);
        $scheduledCheck->method('getSchedule')->willReturn('*/5 * * * *'); // Every 5 minutes
        $scheduledCheck->method('check')->willReturn(
            new HealthCheckResult('scheduled', HealthStatus::HEALTHY, 'OK', 5.0, [], new DateTimeImmutable())
        );
        
        $runner = new HealthCheckRunner($this->logger);
        $runner->registerCheck($scheduledCheck);
        
        $results = $runner->runChecks();
        
        $this->assertArrayHasKey('scheduled', $results);
        $this->assertSame(HealthStatus::HEALTHY, $results['scheduled']->status);
    }
}
