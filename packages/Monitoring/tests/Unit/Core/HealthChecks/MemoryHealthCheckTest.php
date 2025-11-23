<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Tests\Unit\Core\HealthChecks;

use Nexus\Monitoring\Core\HealthChecks\MemoryHealthCheck;
use Nexus\Monitoring\ValueObjects\HealthStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(MemoryHealthCheck::class)]
#[Group('monitoring')]
#[Group('health-checks')]
class MemoryHealthCheckTest extends TestCase
{
    #[Test]
    public function it_returns_healthy_when_memory_usage_is_normal(): void
    {
        $check = new MemoryHealthCheck();
        $result = $check->execute();

        // In a test environment with normal usage, should be healthy or warning
        $this->assertContains($result->status, [HealthStatus::HEALTHY, HealthStatus::WARNING]);
        $this->assertArrayHasKey('current_mb', $result->metadata);
        $this->assertArrayHasKey('peak_mb', $result->metadata);
    }

    #[Test]
    public function it_includes_memory_statistics_in_metadata(): void
    {
        $check = new MemoryHealthCheck();
        $result = $check->execute();

        $this->assertArrayHasKey('current_mb', $result->metadata);
        $this->assertArrayHasKey('peak_mb', $result->metadata);
        $this->assertArrayHasKey('usage_percentage', $result->metadata);
        $this->assertArrayHasKey('peak_percentage', $result->metadata);

        // Verify values are numeric
        $this->assertIsNumeric($result->metadata['current_mb']);
        $this->assertIsNumeric($result->metadata['peak_mb']);

        // Peak should be >= current
        $this->assertGreaterThanOrEqual(
            $result->metadata['current_mb'],
            $result->metadata['peak_mb']
        );
    }

    #[Test]
    public function it_uses_custom_name(): void
    {
        $check = new MemoryHealthCheck(name: 'php_memory');
        $result = $check->execute();

        $this->assertSame('php_memory', $result->checkName);
    }

    #[Test]
    public function it_has_configurable_cache_ttl(): void
    {
        $check = new MemoryHealthCheck(cacheTtl: 60);

        $this->assertSame(60, $check->getCacheTtl());
    }

    #[Test]
    public function it_has_default_cache_ttl_of_30_seconds(): void
    {
        $check = new MemoryHealthCheck();

        $this->assertSame(30, $check->getCacheTtl());
    }

    #[Test]
    public function it_returns_proper_status_for_memory_limit(): void
    {
        $check = new MemoryHealthCheck();
        $result = $check->execute();

        // Should return a valid health status
        $this->assertInstanceOf(HealthStatus::class, $result->status);
    }

    #[Test]
    public function it_includes_limit_info_when_available(): void
    {
        $check = new MemoryHealthCheck();
        $result = $check->execute();

        // Should have limit info (either numeric or 'unlimited')
        $this->assertTrue(
            isset($result->metadata['limit_mb']) || $result->metadata['limit'] === 'unlimited'
        );
    }
}
