<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Entities;

use DateTimeImmutable;
use Nexus\Treasury\Entities\LiquidityPool;
use Nexus\Treasury\Enums\TreasuryStatus;
use Nexus\Common\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class LiquidityPoolTest extends TestCase
{
    public function test_creates_pool_with_required_fields(): void
    {
        $now = new DateTimeImmutable();
        
        $pool = new LiquidityPool(
            id: 'TRE-LIQ-001',
            tenantId: 'tenant-001',
            name: 'Main USD Pool',
            description: 'Primary liquidity pool',
            currency: 'USD',
            totalBalance: Money::of(1000000, 'USD'),
            availableBalance: Money::of(800000, 'USD'),
            reservedBalance: Money::of(200000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001', 'BANK-002'],
            createdAt: $now,
            updatedAt: $now
        );
        
        $this->assertEquals('TRE-LIQ-001', $pool->getId());
        $this->assertEquals('tenant-001', $pool->getTenantId());
        $this->assertEquals('Main USD Pool', $pool->getName());
        $this->assertEquals('USD', $pool->getCurrency());
        $this->assertTrue($pool->isActive());
    }

    public function test_is_active_returns_true_when_status_is_active(): void
    {
        $pool = $this->createPool(TreasuryStatus::ACTIVE);
        
        $this->assertTrue($pool->isActive());
    }

    public function test_is_active_returns_false_when_status_is_not_active(): void
    {
        $pool = $this->createPool(TreasuryStatus::INACTIVE);
        
        $this->assertFalse($pool->isActive());
    }

    public function test_has_sufficient_liquidity_returns_true_when_amount_available(): void
    {
        $pool = $this->createPoolWithBalances(
            Money::of(1000000, 'USD'),
            Money::of(800000, 'USD')
        );
        
        $this->assertTrue($pool->hasSufficientLiquidity(Money::of(500000, 'USD')));
    }

    public function test_has_sufficient_liquidity_returns_true_when_amount_equals_available(): void
    {
        $pool = $this->createPoolWithBalances(
            Money::of(1000000, 'USD'),
            Money::of(800000, 'USD')
        );
        
        $this->assertTrue($pool->hasSufficientLiquidity(Money::of(800000, 'USD')));
    }

    public function test_has_sufficient_liquidity_returns_false_when_amount_exceeds_available(): void
    {
        $pool = $this->createPoolWithBalances(
            Money::of(1000000, 'USD'),
            Money::of(800000, 'USD')
        );
        
        $this->assertFalse($pool->hasSufficientLiquidity(Money::of(900000, 'USD')));
    }

    public function test_has_sufficient_liquidity_returns_false_on_currency_mismatch(): void
    {
        $pool = $this->createPoolWithBalances(
            Money::of(1000000, 'USD'),
            Money::of(800000, 'USD')
        );
        
        $this->assertFalse($pool->hasSufficientLiquidity(Money::of(500000, 'EUR')));
    }

    private function createPool(TreasuryStatus $status): LiquidityPool
    {
        $now = new DateTimeImmutable();
        
        return new LiquidityPool(
            id: 'TRE-LIQ-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(1000000, 'USD'),
            availableBalance: Money::of(800000, 'USD'),
            reservedBalance: Money::of(200000, 'USD'),
            status: $status,
            bankAccountIds: ['BANK-001'],
            createdAt: $now,
            updatedAt: $now
        );
    }

    private function createPoolWithBalances(Money $total, Money $available): LiquidityPool
    {
        $now = new DateTimeImmutable();
        $reserved = $total->subtract($available);
        
        return new LiquidityPool(
            id: 'TRE-LIQ-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: $total,
            availableBalance: $available,
            reservedBalance: $reserved,
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001'],
            createdAt: $now,
            updatedAt: $now
        );
    }
}
