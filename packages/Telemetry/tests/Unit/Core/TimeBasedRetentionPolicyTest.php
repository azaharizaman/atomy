<?php

declare(strict_types=1);

namespace Nexus\Telemetry\Tests\Unit\Core;

use Nexus\Telemetry\Core\TimeBasedRetentionPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TimeBasedRetentionPolicy::class)]
#[Group('monitoring')]
#[Group('retention')]
final class TimeBasedRetentionPolicyTest extends TestCase
{
    #[Test]
    public function it_creates_policy_with_seconds(): void
    {
        $policy = new TimeBasedRetentionPolicy(3600);

        $this->assertSame(3600, $policy->getRetentionPeriod());
    }

    #[Test]
    public function it_creates_policy_with_days_factory(): void
    {
        $policy = TimeBasedRetentionPolicy::days(7);

        $this->assertSame(604800, $policy->getRetentionPeriod()); // 7 * 86400
    }

    #[Test]
    public function it_creates_policy_with_hours_factory(): void
    {
        $policy = TimeBasedRetentionPolicy::hours(24);

        $this->assertSame(86400, $policy->getRetentionPeriod()); // 24 * 3600
    }

    #[Test]
    public function it_rejects_zero_retention_period(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Retention period must be positive');

        new TimeBasedRetentionPolicy(0);
    }

    #[Test]
    public function it_rejects_negative_retention_period(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Retention period must be positive');

        new TimeBasedRetentionPolicy(-100);
    }

    #[Test]
    public function it_retains_recent_metrics(): void
    {
        $policy = TimeBasedRetentionPolicy::days(30);
        $recentTimestamp = time() - (86400 * 10); // 10 days ago

        $this->assertTrue($policy->shouldRetain('api.requests', $recentTimestamp));
    }

    #[Test]
    public function it_does_not_retain_old_metrics(): void
    {
        $policy = TimeBasedRetentionPolicy::days(30);
        $oldTimestamp = time() - (86400 * 40); // 40 days ago

        $this->assertFalse($policy->shouldRetain('api.requests', $oldTimestamp));
    }

    #[Test]
    public function it_retains_metrics_at_exact_cutoff(): void
    {
        $policy = TimeBasedRetentionPolicy::days(7);
        $cutoffTimestamp = time() - (86400 * 7);

        $this->assertTrue($policy->shouldRetain('metric', $cutoffTimestamp));
    }

    #[Test]
    public function it_handles_future_timestamps(): void
    {
        $policy = TimeBasedRetentionPolicy::hours(1);
        $futureTimestamp = time() + 3600; // 1 hour in future

        $this->assertTrue($policy->shouldRetain('metric', $futureTimestamp));
    }

    #[Test]
    public function it_works_with_different_metric_keys(): void
    {
        $policy = TimeBasedRetentionPolicy::days(14);
        $timestamp = time() - (86400 * 7); // 7 days ago

        $this->assertTrue($policy->shouldRetain('api.requests', $timestamp));
        $this->assertTrue($policy->shouldRetain('db.queries', $timestamp));
        $this->assertTrue($policy->shouldRetain('cache.hits', $timestamp));
    }
}
