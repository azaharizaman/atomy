<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Tests\Unit\Core\HealthChecks;

use Nexus\Telemetry\Core\HealthChecks\DiskSpaceHealthCheck;
use Nexus\Telemetry\ValueObjects\HealthStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DiskSpaceHealthCheck::class)]
#[Group('monitoring')]
#[Group('health-checks')]
class DiskSpaceHealthCheckTest extends TestCase
{
    #[Test]
    public function it_returns_healthy_when_disk_usage_is_normal(): void
    {
        $check = new DiskSpaceHealthCheck('/');
        $result = $check->execute();

        // In a test environment, we expect the disk to be healthy
        $this->assertContains($result->status, [HealthStatus::HEALTHY, HealthStatus::WARNING]);
        $this->assertArrayHasKey('total_gb', $result->metadata);
        $this->assertArrayHasKey('used_gb', $result->metadata);
        $this->assertArrayHasKey('free_gb', $result->metadata);
        $this->assertArrayHasKey('usage_percentage', $result->metadata);
    }

    #[Test]
    public function it_returns_critical_when_path_does_not_exist(): void
    {
        $check = new DiskSpaceHealthCheck('/nonexistent/path/that/does/not/exist');
        $result = $check->execute();

        $this->assertSame(HealthStatus::CRITICAL, $result->status);
        $this->assertStringContainsString('does not exist', $result->message);
    }

    #[Test]
    public function it_includes_path_in_metadata(): void
    {
        $check = new DiskSpaceHealthCheck('/tmp');
        $result = $check->execute();

        $this->assertSame('/tmp', $result->metadata['path']);
    }

    #[Test]
    public function it_uses_custom_name(): void
    {
        $check = new DiskSpaceHealthCheck('/', name: 'root_partition');
        $result = $check->execute();

        $this->assertSame('root_partition', $result->checkName);
    }

    #[Test]
    public function it_has_configurable_cache_ttl(): void
    {
        $check = new DiskSpaceHealthCheck('/', cacheTtl: 120);

        $this->assertSame(120, $check->getCacheTtl());
    }

    #[Test]
    public function it_has_default_cache_ttl_of_60_seconds(): void
    {
        $check = new DiskSpaceHealthCheck('/');

        $this->assertSame(60, $check->getCacheTtl());
    }

    #[Test]
    public function it_returns_metadata_with_proper_units(): void
    {
        $check = new DiskSpaceHealthCheck('/');
        $result = $check->execute();

        // Verify all values are numeric
        $this->assertIsNumeric($result->metadata['total_gb']);
        $this->assertIsNumeric($result->metadata['used_gb']);
        $this->assertIsNumeric($result->metadata['free_gb']);
        $this->assertIsNumeric($result->metadata['usage_percentage']);

        // Verify reasonable ranges
        $this->assertGreaterThan(0, $result->metadata['total_gb']);
        $this->assertGreaterThanOrEqual(0, $result->metadata['free_gb']);
        $this->assertGreaterThanOrEqual(0, $result->metadata['usage_percentage']);
        $this->assertLessThanOrEqual(100, $result->metadata['usage_percentage']);
    }
}
