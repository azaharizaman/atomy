<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Tests\Unit\Core\HealthChecks;

use Nexus\Monitoring\Contracts\CacheRepositoryInterface;
use Nexus\Monitoring\Core\HealthChecks\CacheHealthCheck;
use Nexus\Monitoring\ValueObjects\HealthStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheHealthCheck::class)]
#[Group('monitoring')]
#[Group('health-checks')]
class CacheHealthCheckTest extends TestCase
{
    #[Test]
    public function it_returns_healthy_when_cache_operations_succeed(): void
    {
        $cache = $this->createMock(CacheRepositoryInterface::class);
        $cache->method('put')->willReturn(true);
        $cache->method('get')->willReturn('health_check_test_value');
        $cache->method('forget')->willReturn(true);

        $check = new CacheHealthCheck($cache);
        $result = $check->execute();

        $this->assertSame(HealthStatus::HEALTHY, $result->status);
        $this->assertStringContainsString('operational', $result->message);
        $this->assertArrayHasKey('operation_time', $result->metadata);
        $this->assertArrayHasKey('operations_tested', $result->metadata);
    }

    #[Test]
    public function it_returns_critical_when_write_fails(): void
    {
        $cache = $this->createMock(CacheRepositoryInterface::class);
        $cache->method('put')->willReturn(false);

        $check = new CacheHealthCheck($cache);
        $result = $check->execute();

        $this->assertSame(HealthStatus::CRITICAL, $result->status);
        $this->assertStringContainsString('write operation failed', $result->message);
    }

    #[Test]
    public function it_returns_critical_when_read_returns_wrong_value(): void
    {
        $cache = $this->createMock(CacheRepositoryInterface::class);
        $cache->method('put')->willReturn(true);
        $cache->method('get')->willReturn('wrong_value');

        $check = new CacheHealthCheck($cache);
        $result = $check->execute();

        $this->assertSame(HealthStatus::CRITICAL, $result->status);
        $this->assertStringContainsString('read operation failed', $result->message);
    }

    #[Test]
    public function it_returns_warning_when_cache_is_slow(): void
    {
        $cache = $this->createMock(CacheRepositoryInterface::class);
        $cache->method('put')->willReturnCallback(function () {
            usleep(600000); // 0.6 seconds
            return true;
        });
        $cache->method('get')->willReturn('health_check_test_value');
        $cache->method('forget')->willReturn(true);

        $check = new CacheHealthCheck($cache, slowResponseThreshold: 0.5);
        $result = $check->execute();

        $this->assertSame(HealthStatus::WARNING, $result->status);
        $this->assertStringContainsString('slowly', $result->message);
    }

    #[Test]
    public function it_returns_offline_when_cache_throws_exception(): void
    {
        $cache = $this->createMock(CacheRepositoryInterface::class);
        $cache->method('put')->willThrowException(new \RuntimeException('Redis connection failed'));

        $check = new CacheHealthCheck($cache);
        $result = $check->execute();

        $this->assertSame(HealthStatus::OFFLINE, $result->status);
        $this->assertStringContainsString('not accessible', $result->message);
        $this->assertArrayHasKey('error', $result->metadata);
    }

    #[Test]
    public function it_does_not_cache_results_by_default(): void
    {
        $cache = $this->createMock(CacheRepositoryInterface::class);
        $check = new CacheHealthCheck($cache);

        $this->assertNull($check->getCacheTtl());
    }

    #[Test]
    public function it_uses_custom_name(): void
    {
        $cache = $this->createMock(CacheRepositoryInterface::class);
        $cache->method('put')->willReturn(true);
        $cache->method('get')->willReturn('health_check_test_value');

        $check = new CacheHealthCheck($cache, name: 'redis_cache');
        $result = $check->execute();

        $this->assertSame('redis_cache', $result->checkName);
    }
}
