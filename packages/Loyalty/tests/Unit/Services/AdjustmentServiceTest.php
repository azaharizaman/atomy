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
        $secret = 'test-secret';
        $service = new AdjustmentService($logger, $secret);

        $now = new DateTimeImmutable();
        $balance = new PointBalance(1000, 1000, []);
        $tier = new TierStatus('bronze', 'Bronze Status', $now);
        $profile = new LoyaltyProfile('member-123', 'tenant-456', $balance, $tier);

        $expectedMemberHash = hash_hmac('sha256', 'member-123', $secret);
        $expectedTenantHash = hash_hmac('sha256', 'tenant-456', $secret);
        $expectedAdminHash = hash_hmac('sha256', 'admin-99', $secret);

        // Expect logger to be called with pseudonymized info
        $logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Manual point adjustment'), $this->callback(function ($context) use ($expectedMemberHash, $expectedTenantHash, $expectedAdminHash) {
                return $context['member_id'] === $expectedMemberHash 
                    && $context['tenant_id'] === $expectedTenantHash
                    && $context['admin_id'] === $expectedAdminHash
                    && $context['points_delta'] === 500
                    && $context['reason_code'] === 'CORRECTION'
                    && $context['new_balance'] === 1500;
            }));

        $newBalance = $service->adjustPoints($profile, 500, 'CORRECTION', 'admin-99');

        $this->assertEquals(1500, $newBalance);
    }

    public function test_it_clamps_negative_balance(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $service = new AdjustmentService($logger);

        $now = new DateTimeImmutable();
        $balance = new PointBalance(100, 100, []);
        $tier = new TierStatus('bronze', 'Bronze Status', $now);
        $profile = new LoyaltyProfile('m1', 't1', $balance, $tier);

        // Deduct more than available
        $newBalance = $service->adjustPoints($profile, -500, 'CORRECTION', 'admin-99');

        $this->assertEquals(0, $newBalance);
    }

    public function test_it_sanitizes_metadata(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $service = new AdjustmentService($logger);

        $now = new DateTimeImmutable();
        $balance = new PointBalance(100, 100, []);
        $tier = new TierStatus('bronze', 'Bronze Status', $now);
        $profile = new LoyaltyProfile('m1', 't1', $balance, $tier);

        $metadata = [
            'channel' => 'web',
            'sensitive_key' => 'secret_value', // Should be removed
            'campaign_id' => 'promo-1'
        ];

        $logger->expects($this->once())
            ->method('info')
            ->with($this->anything(), $this->callback(function ($context) {
                return count($context['metadata']) === 2 
                    && isset($context['metadata']['channel'])
                    && isset($context['metadata']['campaign_id'])
                    && !isset($context['metadata']['sensitive_key']);
            }));

        $service->adjustPoints($profile, 10, 'TEST', 'admin', $metadata);
    }
}
