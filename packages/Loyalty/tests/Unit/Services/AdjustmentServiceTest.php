<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Loyalty\Models\LoyaltyProfile;
use Nexus\Loyalty\Models\PointBalance;
use Nexus\Loyalty\Models\TierStatus;
use Nexus\Loyalty\Services\AdjustmentService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class AdjustmentServiceTest extends TestCase
{
    public function test_manual_adjustment_audit(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $service = new AdjustmentService($logger);

        $now = new DateTimeImmutable();
        $balance = new PointBalance(1000, 1000, []);
        $tier = new TierStatus('bronze', 'Bronze Status', $now);
        $profile = new LoyaltyProfile('member-123', 'tenant-456', $balance, $tier);

        // Expect logger to be called with adjustment info
        $logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Manual point adjustment'), $this->callback(function ($context) {
                return $context['member_id'] === 'member-123' 
                    && $context['points_delta'] === 500
                    && $context['reason_code'] === 'CORRECTION'
                    && $context['admin_id'] === 'admin-99';
            }));

        $newBalance = $service->adjustPoints($profile, 500, 'CORRECTION', 'admin-99');

        $this->assertEquals(1500, $newBalance);
    }
}
