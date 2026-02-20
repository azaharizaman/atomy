<?php

declare(strict_types=1);

namespace Nexus\Treasury\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\ValueObjects\LiquidityPoolBalance;
use PHPUnit\Framework\TestCase;

final class LiquidityPoolBalanceTest extends TestCase
{
    public function test_creates_with_required_fields(): void
    {
        $date = new DateTimeImmutable();

        $balance = new LiquidityPoolBalance(
            poolId: 'pool-001',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            calculatedAt: $date
        );

        $this->assertEquals('pool-001', $balance->poolId);
        $this->assertEquals(100000, $balance->totalBalance->getAmount());
        $this->assertEquals(80000, $balance->availableBalance->getAmount());
        $this->assertEquals(20000, $balance->reservedBalance->getAmount());
    }

    public function test_from_array_creates_balance(): void
    {
        $balance = LiquidityPoolBalance::fromArray([
            'pool_id' => 'pool-001',
            'total_balance' => 100000,
            'available_balance' => 80000,
            'reserved_balance' => 20000,
            'calculated_at' => '2024-01-15 10:00:00',
            'currency' => 'USD',
        ]);

        $this->assertEquals('pool-001', $balance->poolId);
        $this->assertEquals(100000, $balance->totalBalance->getAmount());
    }

    public function test_to_array_returns_array(): void
    {
        $balance = new LiquidityPoolBalance(
            poolId: 'pool-001',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            calculatedAt: new DateTimeImmutable()
        );

        $array = $balance->toArray();

        $this->assertEquals('pool-001', $array['poolId']);
        $this->assertIsArray($array['totalBalance']);
    }

    public function test_get_currency_returns_currency(): void
    {
        $balance = new LiquidityPoolBalance(
            poolId: 'pool-001',
            totalBalance: Money::of(100000, 'EUR'),
            availableBalance: Money::of(80000, 'EUR'),
            reservedBalance: Money::of(20000, 'EUR'),
            calculatedAt: new DateTimeImmutable()
        );

        $this->assertEquals('EUR', $balance->getCurrency());
    }

    public function test_utilization_percentage_calculates_correctly(): void
    {
        $balance = new LiquidityPoolBalance(
            poolId: 'pool-001',
            totalBalance: Money::of(100000, 'USD'),
            availableBalance: Money::of(80000, 'USD'),
            reservedBalance: Money::of(20000, 'USD'),
            calculatedAt: new DateTimeImmutable()
        );

        $this->assertEquals(20.0, $balance->utilizationPercentage());
    }

    public function test_utilization_percentage_returns_zero_for_zero_total(): void
    {
        $balance = new LiquidityPoolBalance(
            poolId: 'pool-001',
            totalBalance: Money::of(0, 'USD'),
            availableBalance: Money::of(0, 'USD'),
            reservedBalance: Money::of(0, 'USD'),
            calculatedAt: new DateTimeImmutable()
        );

        $this->assertEquals(0.0, $balance->utilizationPercentage());
    }
}
