<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\LiquidityPoolQueryInterface;
use Nexus\Treasury\Contracts\LiquidityPoolPersistInterface;
use Nexus\Treasury\Entities\LiquidityPool;
use Nexus\Treasury\Enums\TreasuryStatus;
use Nexus\Treasury\Exceptions\LiquidityPoolNotFoundException;
use Nexus\Treasury\Services\LiquidityPoolService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class LiquidityPoolServiceTest extends TestCase
{
    private LiquidityPoolService $service;
    private MockObject $query;
    private MockObject $persist;

    protected function setUp(): void
    {
        $this->query = $this->createMock(LiquidityPoolQueryInterface::class);
        $this->persist = $this->createMock(LiquidityPoolPersistInterface::class);

        $this->service = new LiquidityPoolService(
            $this->query,
            $this->persist,
            null,
            new NullLogger()
        );
    }

    public function test_create_creates_pool(): void
    {
        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->create(
            'tenant-001',
            'Main USD Pool',
            'USD',
            ['BANK-001', 'BANK-002'],
            'Primary liquidity pool'
        );

        $this->assertEquals('tenant-001', $result->getTenantId());
        $this->assertEquals('Main USD Pool', $result->getName());
        $this->assertEquals('USD', $result->getCurrency());
        $this->assertEquals('Primary liquidity pool', $result->getDescription());
        $this->assertEquals(['BANK-001', 'BANK-002'], $result->getBankAccountIds());
        $this->assertEquals(TreasuryStatus::PENDING, $result->getStatus());
    }

    public function test_activate_activates_pool(): void
    {
        $pool = $this->createPool(TreasuryStatus::PENDING);

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('POOL-001')
            ->willReturn($pool);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->activate('POOL-001');

        $this->assertEquals(TreasuryStatus::ACTIVE, $result->getStatus());
    }

    public function test_deactivate_deactivates_pool(): void
    {
        $pool = $this->createPool(TreasuryStatus::ACTIVE);

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('POOL-001')
            ->willReturn($pool);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->deactivate('POOL-001');

        $this->assertEquals(TreasuryStatus::INACTIVE, $result->getStatus());
    }

    public function test_get_returns_pool(): void
    {
        $pool = $this->createPool(TreasuryStatus::ACTIVE);

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('POOL-001')
            ->willReturn($pool);

        $result = $this->service->get('POOL-001');

        $this->assertEquals($pool, $result);
    }

    public function test_get_by_tenant_returns_pools(): void
    {
        $pool = $this->createPool(TreasuryStatus::ACTIVE);

        $this->query
            ->expects($this->once())
            ->method('findByTenantId')
            ->with('tenant-001')
            ->willReturn([$pool]);

        $result = $this->service->getByTenant('tenant-001');

        $this->assertCount(1, $result);
    }

    public function test_get_active_returns_active_pools(): void
    {
        $pool = $this->createPool(TreasuryStatus::ACTIVE);

        $this->query
            ->expects($this->once())
            ->method('findActiveByTenantId')
            ->with('tenant-001')
            ->willReturn([$pool]);

        $result = $this->service->getActive('tenant-001');

        $this->assertCount(1, $result);
    }

    public function test_reserve_funds_reserves_amount(): void
    {
        $pool = new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('POOL-001')
            ->willReturn($pool);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->reserveFunds('POOL-001', Money::of(30000, 'USD'));

        $this->assertEquals(50000, $result->getAvailableBalance()->getAmount());
        $this->assertEquals(50000, $result->getReservedBalance()->getAmount());
    }

    public function test_reserve_funds_throws_when_insufficient(): void
    {
        $pool = new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(20000, 'USD'),
            reservedBalance: Money::of(80000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('POOL-001')
            ->willReturn($pool);

        $this->expectException(LiquidityPoolNotFoundException::class);

        $this->service->reserveFunds('POOL-001', Money::of(50000, 'USD'));
    }

    public function test_release_funds_releases_amount(): void
    {
        $pool = new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(50000, 'USD'),
            reservedBalance: Money::of(50000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('POOL-001')
            ->willReturn($pool);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->releaseFunds('POOL-001', Money::of(30000, 'USD'));

        $this->assertEquals(80000, $result->getAvailableBalance()->getAmount());
        $this->assertEquals(20000, $result->getReservedBalance()->getAmount());
    }

    public function test_add_bank_account_adds_account(): void
    {
        $pool = new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(100000, 'USD'),
            reservedBalance: Money::of(0, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('POOL-001')
            ->willReturn($pool);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->addBankAccount('POOL-001', 'BANK-002');

        $this->assertCount(2, $result->getBankAccountIds());
        $this->assertContains('BANK-002', $result->getBankAccountIds());
    }

    public function test_remove_bank_account_removes_account(): void
    {
        $pool = new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(100000, 'USD'),
            reservedBalance: Money::of(0, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001', 'BANK-002'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('POOL-001')
            ->willReturn($pool);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->removeBankAccount('POOL-001', 'BANK-001');

        $this->assertCount(1, $result->getBankAccountIds());
        $this->assertNotContains('BANK-001', $result->getBankAccountIds());
    }

    public function test_delete_deletes_pool(): void
    {
        $pool = $this->createPool(TreasuryStatus::ACTIVE);

        $this->query
            ->expects($this->once())
            ->method('find')
            ->with('POOL-001')
            ->willReturn($pool);

        $this->persist
            ->expects($this->once())
            ->method('delete')
            ->with('POOL-001');

        $this->service->delete('POOL-001');
    }

    public function test_delete_throws_exception_when_pool_not_found(): void
    {
        $this->query
            ->expects($this->once())
            ->method('find')
            ->with('POOL-001')
            ->willReturn(null);

        $this->expectException(LiquidityPoolNotFoundException::class);

        $this->service->delete('POOL-001');
    }

    public function test_release_funds_caps_at_reserved_balance(): void
    {
        $pool = new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('POOL-001')
            ->willReturn($pool);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->releaseFunds('POOL-001', Money::of(50000, 'USD'));

        $this->assertEquals(100000, $result->getAvailableBalance()->getAmount());
        $this->assertEquals(0, $result->getReservedBalance()->getAmount());
    }

    public function test_calculate_balance_without_cash_provider(): void
    {
        $pool = new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('POOL-001')
            ->willReturn($pool);

        $result = $this->service->calculateBalance('POOL-001');

        $this->assertEquals('POOL-001', $result->poolId);
        $this->assertEquals(100000, $result->totalBalance->getAmount());
        $this->assertEquals(80000, $result->availableBalance->getAmount());
        $this->assertEquals(20000, $result->reservedBalance->getAmount());
    }

    public function test_refresh_balances_updates_pool(): void
    {
        $pool = new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->expects($this->exactly(2))
            ->method('findOrFail')
            ->with('POOL-001')
            ->willReturn($pool);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->refreshBalances('POOL-001');

        $this->assertEquals(100000, $result->getTotalBalance()->getAmount());
    }

    public function test_add_bank_account_does_not_duplicate(): void
    {
        $pool = new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(100000, 'USD'),
            reservedBalance: Money::of(0, 'USD'),
            status: TreasuryStatus::ACTIVE,
            bankAccountIds: ['BANK-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );

        $this->query
            ->expects($this->once())
            ->method('findOrFail')
            ->with('POOL-001')
            ->willReturn($pool);

        $this->persist
            ->expects($this->once())
            ->method('save');

        $result = $this->service->addBankAccount('POOL-001', 'BANK-001');

        $this->assertCount(1, $result->getBankAccountIds());
    }

    private function createPool(TreasuryStatus $status): LiquidityPool
    {
        return new LiquidityPool(
            id: 'POOL-001',
            tenantId: 'tenant-001',
            name: 'Test Pool',
            description: null,
            currency: 'USD',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            status: $status,
            bankAccountIds: ['BANK-001'],
            createdAt: new DateTimeImmutable(),
            updatedAt: new DateTimeImmutable()
        );
    }
}
