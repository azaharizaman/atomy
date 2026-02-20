<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\ValueObjects\TreasuryPosition;
use PHPUnit\Framework\TestCase;

final class TreasuryPositionTest extends TestCase
{
    public function test_creates_with_required_fields(): void
    {
        $date = new DateTimeImmutable();

        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: 'entity-001',
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(50000, 'USD'),
            projectedOutflows: Money::of(30000, 'USD'),
            positionDate: $date
        );

        $this->assertEquals('tenant-001', $position->tenantId);
        $this->assertEquals('entity-001', $position->entityId);
        $this->assertEquals(100000, $position->totalCashBalance->getAmount());
        $this->assertEquals(80000, $position->availableCashBalance->getAmount());
    }

    public function test_from_array_creates_position(): void
    {
        $position = TreasuryPosition::fromArray([
            'tenant_id' => 'tenant-001',
            'entity_id' => 'entity-001',
            'total_cash_balance' => 100000,
            'available_cash_balance' => 80000,
            'reserved_cash_balance' => 10000,
            'invested_cash_balance' => 10000,
            'projected_inflows' => 50000,
            'projected_outflows' => 30000,
            'position_date' => '2024-01-15',
            'currency' => 'USD',
        ]);

        $this->assertEquals('tenant-001', $position->tenantId);
        $this->assertEquals('entity-001', $position->entityId);
    }

    public function test_to_array_returns_array(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(50000, 'USD'),
            projectedOutflows: Money::of(30000, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $array = $position->toArray();

        $this->assertEquals('tenant-001', $array['tenantId']);
        $this->assertNull($array['entityId']);
        $this->assertIsArray($array['totalCashBalance']);
    }

    public function test_get_currency_returns_currency(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'EUR'),
            availableCashBalance: Money::of(80000, 'EUR'),
            reservedCashBalance: Money::of(10000, 'EUR'),
            investedCashBalance: Money::of(10000, 'EUR'),
            projectedInflows: Money::of(50000, 'EUR'),
            projectedOutflows: Money::of(30000, 'EUR'),
            positionDate: new DateTimeImmutable()
        );

        $this->assertEquals('EUR', $position->getCurrency());
    }

    public function test_get_net_cash_flow_calculates_correctly(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(50000, 'USD'),
            projectedOutflows: Money::of(30000, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $netCashFlow = $position->getNetCashFlow();

        $this->assertEquals(20000, $netCashFlow->getAmount());
    }

    public function test_get_net_position_calculates_correctly(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(50000, 'USD'),
            projectedOutflows: Money::of(30000, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $netPosition = $position->getNetPosition();

        $this->assertEquals(100000, $netPosition->getAmount());
    }

    public function test_has_sufficient_liquidity_returns_true_when_sufficient(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(0, 'USD'),
            projectedOutflows: Money::of(0, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $this->assertTrue($position->hasSufficientLiquidity(Money::of(50000, 'USD')));
    }

    public function test_has_sufficient_liquidity_returns_false_when_insufficient(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(0, 'USD'),
            projectedOutflows: Money::of(0, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $this->assertFalse($position->hasSufficientLiquidity(Money::of(100000, 'USD')));
    }

    public function test_get_id_returns_string(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(0, 'USD'),
            projectedOutflows: Money::of(0, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $this->assertStringStartsWith('TRE-POS-', $position->getId());
    }

    public function test_get_tenant_id_returns_string(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(0, 'USD'),
            projectedOutflows: Money::of(0, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $this->assertEquals('tenant-001', $position->getTenantId());
    }

    public function test_get_entity_id_returns_value(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: 'entity-001',
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(0, 'USD'),
            projectedOutflows: Money::of(0, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $this->assertEquals('entity-001', $position->getEntityId());
    }

    public function test_get_position_date_returns_date(): void
    {
        $date = new DateTimeImmutable('2026-01-15');
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(0, 'USD'),
            projectedOutflows: Money::of(0, 'USD'),
            positionDate: $date
        );

        $this->assertEquals($date, $position->getPositionDate());
    }

    public function test_get_total_cash_balance_returns_money(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(0, 'USD'),
            projectedOutflows: Money::of(0, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $this->assertEquals(100000, $position->getTotalCashBalance()->getAmount());
    }

    public function test_get_available_cash_balance_returns_money(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(0, 'USD'),
            projectedOutflows: Money::of(0, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $this->assertEquals(80000, $position->getAvailableCashBalance()->getAmount());
    }

    public function test_get_reserved_cash_balance_returns_money(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(15000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(0, 'USD'),
            projectedOutflows: Money::of(0, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $this->assertEquals(15000, $position->getReservedCashBalance()->getAmount());
    }

    public function test_get_invested_cash_balance_returns_money(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(50000, 'USD'),
            projectedInflows: Money::of(0, 'USD'),
            projectedOutflows: Money::of(0, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $this->assertEquals(50000, $position->getInvestedCashBalance()->getAmount());
    }

    public function test_get_projected_inflows_returns_money(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(25000, 'USD'),
            projectedOutflows: Money::of(0, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $this->assertEquals(25000, $position->getProjectedInflows()->getAmount());
    }

    public function test_get_projected_outflows_returns_money(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(0, 'USD'),
            projectedOutflows: Money::of(15000, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $this->assertEquals(15000, $position->getProjectedOutflows()->getAmount());
    }

    public function test_get_created_at_returns_date(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(0, 'USD'),
            projectedOutflows: Money::of(0, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $this->assertInstanceOf(DateTimeImmutable::class, $position->getCreatedAt());
    }

    public function test_get_updated_at_returns_date(): void
    {
        $position = new TreasuryPosition(
            tenantId: 'tenant-001',
            entityId: null,
            totalCashBalance: Money::of(100000, 'USD'),
            availableCashBalance: Money::of(80000, 'USD'),
            reservedCashBalance: Money::of(10000, 'USD'),
            investedCashBalance: Money::of(10000, 'USD'),
            projectedInflows: Money::of(0, 'USD'),
            projectedOutflows: Money::of(0, 'USD'),
            positionDate: new DateTimeImmutable()
        );

        $this->assertInstanceOf(DateTimeImmutable::class, $position->getUpdatedAt());
    }
}
