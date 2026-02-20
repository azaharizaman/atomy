<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Entities;

use DateTimeImmutable;
use Nexus\Treasury\Entities\AuthorizationLimit;
use Nexus\Common\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class AuthorizationLimitTest extends TestCase
{
    public function test_creates_limit_with_required_fields(): void
    {
        $now = new DateTimeImmutable();
        
        $limit = new AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: 'user-001',
            roleId: null,
            transactionType: 'payment',
            approvalLimit: Money::of(50000, 'USD'),
            dailyLimit: Money::of(100000, 'USD'),
            weeklyLimit: Money::of(500000, 'USD'),
            monthlyLimit: Money::of(2000000, 'USD'),
            requiresDualApproval: true,
            effectiveFrom: $now,
            effectiveTo: null,
            createdAt: $now,
            updatedAt: $now
        );
        
        $this->assertEquals('TRE-AUTH-001', $limit->getId());
        $this->assertEquals('tenant-001', $limit->getTenantId());
        $this->assertEquals('user-001', $limit->getUserId());
        $this->assertEquals('payment', $limit->getTransactionType());
        $this->assertTrue($limit->getRequiresDualApproval());
    }

    public function test_is_active_returns_true_when_effective_now(): void
    {
        $limit = $this->createLimit(new DateTimeImmutable(), null);
        
        $this->assertTrue($limit->isActive());
    }

    public function test_is_active_returns_false_when_not_yet_effective(): void
    {
        $futureDate = new DateTimeImmutable('+1 month');
        
        $limit = $this->createLimit($futureDate, null);
        
        $this->assertFalse($limit->isActive());
    }

    public function test_is_active_returns_false_when_expired(): void
    {
        $pastDate = new DateTimeImmutable('-1 month');
        $yesterday = new DateTimeImmutable('-1 day');
        
        $limit = $this->createLimit($pastDate, $yesterday);
        
        $this->assertFalse($limit->isActive());
    }

    public function test_can_authorize_returns_true_when_amount_within_limit(): void
    {
        $limit = $this->createLimit(new DateTimeImmutable(), null);
        
        $result = $limit->canAuthorize(Money::of(40000, 'USD'));
        
        $this->assertTrue($result);
    }

    public function test_can_authorize_returns_true_when_amount_equals_limit(): void
    {
        $limit = $this->createLimit(new DateTimeImmutable(), null);
        
        $result = $limit->canAuthorize(Money::of(50000, 'USD'));
        
        $this->assertTrue($result);
    }

    public function test_can_authorize_returns_false_when_amount_exceeds_limit(): void
    {
        $limit = $this->createLimit(new DateTimeImmutable(), null);
        
        $result = $limit->canAuthorize(Money::of(60000, 'USD'));
        
        $this->assertFalse($result);
    }

    public function test_can_authorize_returns_false_when_currency_mismatch(): void
    {
        $limit = $this->createLimit(new DateTimeImmutable(), null);
        
        $result = $limit->canAuthorize(Money::of(40000, 'EUR'));
        
        $this->assertFalse($result);
    }

    private function createLimit(
        DateTimeImmutable $effectiveFrom,
        ?DateTimeImmutable $effectiveTo
    ): AuthorizationLimit {
        $now = new DateTimeImmutable();
        
        return new AuthorizationLimit(
            id: 'TRE-AUTH-001',
            tenantId: 'tenant-001',
            userId: 'user-001',
            roleId: null,
            transactionType: 'payment',
            approvalLimit: Money::of(50000, 'USD'),
            dailyLimit: Money::of(100000, 'USD'),
            weeklyLimit: Money::of(500000, 'USD'),
            monthlyLimit: Money::of(2000000, 'USD'),
            requiresDualApproval: false,
            effectiveFrom: $effectiveFrom,
            effectiveTo: $effectiveTo,
            createdAt: $now,
            updatedAt: $now
        );
    }
}
