<?php

declare(strict_types=1);

namespace Nexus\Monitoring\Tests\Unit\ValueObjects;

use Nexus\Monitoring\ValueObjects\HealthStatus;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[Group('monitoring')]
#[Group('value-objects')]
final class HealthStatusTest extends TestCase
{
    #[Test]
    public function it_has_healthy_status(): void
    {
        $this->assertSame('healthy', HealthStatus::HEALTHY->value);
    }

    #[Test]
    public function it_has_warning_status(): void
    {
        $this->assertSame('warning', HealthStatus::WARNING->value);
    }

    #[Test]
    public function it_has_degraded_status(): void
    {
        $this->assertSame('degraded', HealthStatus::DEGRADED->value);
    }

    #[Test]
    public function it_has_critical_status(): void
    {
        $this->assertSame('critical', HealthStatus::CRITICAL->value);
    }

    #[Test]
    public function it_has_offline_status(): void
    {
        $this->assertSame('offline', HealthStatus::OFFLINE->value);
    }

    #[Test]
    #[DataProvider('severityWeightProvider')]
    public function it_returns_correct_severity_weight(HealthStatus $status, int $expectedWeight): void
    {
        $this->assertSame($expectedWeight, $status->getSeverityWeight());
    }

    #[Test]
    #[DataProvider('healthyStatusProvider')]
    public function it_identifies_healthy_status(HealthStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->isHealthy());
    }

    #[Test]
    #[DataProvider('criticalStatusProvider')]
    public function it_identifies_critical_status(HealthStatus $status, bool $expected): void
    {
        $this->assertSame($expected, $status->isCritical());
    }

    #[Test]
    #[DataProvider('statusLabelProvider')]
    public function it_returns_correct_label(HealthStatus $status, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $status->label());
    }

    #[Test]
    public function it_sorts_statuses_by_severity_ascending(): void
    {
        $statuses = [
            HealthStatus::OFFLINE,
            HealthStatus::HEALTHY,
            HealthStatus::CRITICAL,
            HealthStatus::WARNING,
            HealthStatus::DEGRADED,
        ];

        usort($statuses, fn($a, $b) => $a->getSeverityWeight() <=> $b->getSeverityWeight());

        $this->assertSame(HealthStatus::HEALTHY, $statuses[0]);
        $this->assertSame(HealthStatus::WARNING, $statuses[1]);
        $this->assertSame(HealthStatus::DEGRADED, $statuses[2]);
        $this->assertSame(HealthStatus::CRITICAL, $statuses[3]);
        $this->assertSame(HealthStatus::OFFLINE, $statuses[4]);
    }

    public static function severityWeightProvider(): array
    {
        return [
            'HEALTHY weight is 0' => [HealthStatus::HEALTHY, 0],
            'WARNING weight is 25' => [HealthStatus::WARNING, 25],
            'DEGRADED weight is 50' => [HealthStatus::DEGRADED, 50],
            'CRITICAL weight is 75' => [HealthStatus::CRITICAL, 75],
            'OFFLINE weight is 100' => [HealthStatus::OFFLINE, 100],
        ];
    }

    public static function healthyStatusProvider(): array
    {
        return [
            'HEALTHY is healthy' => [HealthStatus::HEALTHY, true],
            'WARNING is not healthy' => [HealthStatus::WARNING, false],
            'DEGRADED is not healthy' => [HealthStatus::DEGRADED, false],
            'CRITICAL is not healthy' => [HealthStatus::CRITICAL, false],
            'OFFLINE is not healthy' => [HealthStatus::OFFLINE, false],
        ];
    }

    public static function criticalStatusProvider(): array
    {
        return [
            'HEALTHY is not critical' => [HealthStatus::HEALTHY, false],
            'WARNING is not critical' => [HealthStatus::WARNING, false],
            'DEGRADED is not critical' => [HealthStatus::DEGRADED, false],
            'CRITICAL is critical' => [HealthStatus::CRITICAL, true],
            'OFFLINE is critical' => [HealthStatus::OFFLINE, true],
        ];
    }

    public static function statusLabelProvider(): array
    {
        return [
            'HEALTHY label' => [HealthStatus::HEALTHY, 'Healthy'],
            'WARNING label' => [HealthStatus::WARNING, 'Warning'],
            'DEGRADED label' => [HealthStatus::DEGRADED, 'Degraded'],
            'CRITICAL label' => [HealthStatus::CRITICAL, 'Critical'],
            'OFFLINE label' => [HealthStatus::OFFLINE, 'Offline'],
        ];
    }
}
