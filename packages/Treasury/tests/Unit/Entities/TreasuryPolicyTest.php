<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Entities;

use DateTimeImmutable;
use Nexus\Treasury\Entities\TreasuryPolicy;
use Nexus\Treasury\Enums\TreasuryStatus;
use PHPUnit\Framework\TestCase;

final class TreasuryPolicyTest extends TestCase
{
    public function test_creates_policy_with_required_fields(): void
    {
        $now = new DateTimeImmutable();
        $effectiveFrom = new DateTimeImmutable('2024-01-01');
        
        $policy = new TreasuryPolicy(
            id: 'TRE-POL-001',
            tenantId: 'tenant-001',
            name: 'Default Treasury Policy',
            minimumCashBalance: \Nexus\Common\ValueObjects\Money::of(10000, 'USD'),
            maximumSingleTransaction: \Nexus\Common\ValueObjects\Money::of(50000, 'USD'),
            approvalThreshold: \Nexus\Common\ValueObjects\Money::of(5000, 'USD'),
            approvalRequired: true,
            status: TreasuryStatus::ACTIVE,
            effectiveFrom: $effectiveFrom,
            effectiveTo: null,
            description: 'Default policy',
            createdAt: $now,
            updatedAt: $now
        );
        
        $this->assertEquals('TRE-POL-001', $policy->getId());
        $this->assertEquals('tenant-001', $policy->getTenantId());
        $this->assertEquals('Default Treasury Policy', $policy->getName());
        $this->assertEquals('Default policy', $policy->getDescription());
        $this->assertTrue($policy->isApprovalRequired());
        $this->assertTrue($policy->isActive());
    }

    public function test_is_active_returns_true_when_status_is_active(): void
    {
        $policy = $this->createPolicy(TreasuryStatus::ACTIVE);
        
        $this->assertTrue($policy->isActive());
    }

    public function test_is_active_returns_false_when_status_is_not_active(): void
    {
        $policy = $this->createPolicy(TreasuryStatus::INACTIVE);
        
        $this->assertFalse($policy->isActive());
    }

    public function test_is_effecticient_returns_true_when_date_is_within_range(): void
    {
        $effectiveFrom = new DateTimeImmutable('2024-01-01');
        $effectiveTo = new DateTimeImmutable('2024-12-31');
        
        $policy = $this->createPolicy(TreasuryStatus::ACTIVE, $effectiveFrom, $effectiveTo);
        
        $this->assertTrue($policy->isEffective(new DateTimeImmutable('2024-06-15')));
    }

    public function test_is_effecticient_returns_false_when_date_is_before_range(): void
    {
        $effectiveFrom = new DateTimeImmutable('2024-06-01');
        
        $policy = $this->createPolicy(TreasuryStatus::ACTIVE, $effectiveFrom);
        
        $this->assertFalse($policy->isEffective(new DateTimeImmutable('2024-01-15')));
    }

    public function test_is_effecticient_returns_false_when_date_is_after_range(): void
    {
        $effectiveFrom = new DateTimeImmutable('2024-01-01');
        $effectiveTo = new DateTimeImmutable('2024-06-30');
        
        $policy = $this->createPolicy(TreasuryStatus::ACTIVE, $effectiveFrom, $effectiveTo);
        
        $this->assertFalse($policy->isEffective(new DateTimeImmutable('2024-12-15')));
    }

    public function test_is_effecticient_returns_false_when_status_is_not_active(): void
    {
        $policy = $this->createPolicy(TreasuryStatus::INACTIVE);
        
        $this->assertFalse($policy->isEffective(new DateTimeImmutable()));
    }

    private function createPolicy(
        TreasuryStatus $status,
        ?DateTimeImmutable $effectiveFrom = null,
        ?DateTimeImmutable $effectiveTo = null
    ): TreasuryPolicy {
        $now = new DateTimeImmutable();
        
        return new TreasuryPolicy(
            id: 'TRE-POL-001',
            tenantId: 'tenant-001',
            name: 'Test Policy',
            minimumCashBalance: \Nexus\Common\ValueObjects\Money::of(10000, 'USD'),
            maximumSingleTransaction: \Nexus\Common\ValueObjects\Money::of(50000, 'USD'),
            approvalThreshold: \Nexus\Common\ValueObjects\Money::of(5000, 'USD'),
            approvalRequired: true,
            status: $status,
            effectiveFrom: $effectiveFrom ?? $now,
            effectiveTo: $effectiveTo,
            description: null,
            createdAt: $now,
            updatedAt: $now
        );
    }
}
