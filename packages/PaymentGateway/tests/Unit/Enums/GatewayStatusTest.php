<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Enums;

use Nexus\PaymentGateway\Enums\GatewayStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(GatewayStatus::class)]
final class GatewayStatusTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_statuses(): void
    {
        $expectedStatuses = [
            'HEALTHY',
            'DEGRADED',
            'UNHEALTHY',
            'MAINTENANCE',
            'UNAVAILABLE',
            'UNKNOWN',
        ];

        $actualStatuses = array_map(
            fn (GatewayStatus $status) => $status->name,
            GatewayStatus::cases()
        );

        $this->assertSame($expectedStatuses, $actualStatuses);
    }

    #[Test]
    public function it_returns_correct_labels(): void
    {
        $this->assertSame('Healthy', GatewayStatus::HEALTHY->label());
        $this->assertSame('Degraded', GatewayStatus::DEGRADED->label());
        $this->assertSame('Unhealthy', GatewayStatus::UNHEALTHY->label());
        $this->assertSame('Under Maintenance', GatewayStatus::MAINTENANCE->label());
        $this->assertSame('Unknown', GatewayStatus::UNKNOWN->label());
        $this->assertSame('Unavailable', GatewayStatus::UNAVAILABLE->label());
    }

    #[Test]
    public function healthy_and_degraded_are_operational(): void
    {
        $this->assertTrue(GatewayStatus::HEALTHY->isOperational());
        $this->assertTrue(GatewayStatus::DEGRADED->isOperational());
    }

    #[Test]
    public function unhealthy_maintenance_unknown_unavailable_are_not_operational(): void
    {
        $this->assertFalse(GatewayStatus::UNHEALTHY->isOperational());
        $this->assertFalse(GatewayStatus::MAINTENANCE->isOperational());
        $this->assertFalse(GatewayStatus::UNKNOWN->isOperational());
        $this->assertFalse(GatewayStatus::UNAVAILABLE->isOperational());
    }

    #[Test]
    public function only_degraded_is_backup_only(): void
    {
        $this->assertFalse(GatewayStatus::HEALTHY->isBackupOnly());
        $this->assertTrue(GatewayStatus::DEGRADED->isBackupOnly());
        $this->assertFalse(GatewayStatus::UNHEALTHY->isBackupOnly());
        $this->assertFalse(GatewayStatus::MAINTENANCE->isBackupOnly());
        $this->assertFalse(GatewayStatus::UNKNOWN->isBackupOnly());
        $this->assertFalse(GatewayStatus::UNAVAILABLE->isBackupOnly());
    }

    #[Test]
    public function it_has_correct_backing_values(): void
    {
        $this->assertSame('healthy', GatewayStatus::HEALTHY->value);
        $this->assertSame('degraded', GatewayStatus::DEGRADED->value);
        $this->assertSame('unhealthy', GatewayStatus::UNHEALTHY->value);
        $this->assertSame('maintenance', GatewayStatus::MAINTENANCE->value);
        $this->assertSame('unknown', GatewayStatus::UNKNOWN->value);
        $this->assertSame('unavailable', GatewayStatus::UNAVAILABLE->value);
    }
}
