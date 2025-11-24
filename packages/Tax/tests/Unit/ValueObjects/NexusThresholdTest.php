<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\ValueObjects;

use Nexus\Currency\ValueObjects\Money;
use Nexus\Tax\ValueObjects\NexusThreshold;
use PHPUnit\Framework\TestCase;

final class NexusThresholdTest extends TestCase
{
    public function test_it_can_be_created_with_valid_data(): void
    {
        $threshold = new NexusThreshold(
            jurisdictionCode: 'US-CA',
            revenueThreshold: Money::of('500000.00', 'USD'),
            transactionThreshold: 200,
            effectiveFrom: new \DateTimeImmutable('2024-01-01'),
            calculationPeriod: 'calendar_year',
        );

        $this->assertSame('US-CA', $threshold->jurisdictionCode);
        $this->assertSame('500000.00', $threshold->revenueThreshold->getAmount());
        $this->assertSame(200, $threshold->transactionThreshold);
        $this->assertSame('calendar_year', $threshold->calculationPeriod);
    }

    public function test_it_validates_empty_jurisdiction_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Jurisdiction code cannot be empty');

        new NexusThreshold(
            jurisdictionCode: '',
            revenueThreshold: Money::of('500000.00', 'USD'),
        );
    }

    public function test_it_validates_negative_revenue_threshold(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Revenue threshold cannot be negative');

        new NexusThreshold(
            jurisdictionCode: 'US-CA',
            revenueThreshold: Money::of('-500000.00', 'USD'),
        );
    }

    public function test_it_validates_negative_transaction_threshold(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction threshold cannot be negative');

        new NexusThreshold(
            jurisdictionCode: 'US-CA',
            revenueThreshold: Money::of('500000.00', 'USD'),
            transactionThreshold: -100,
        );
    }

    public function test_it_validates_effective_date_range(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('effectiveTo must be after effectiveFrom');

        new NexusThreshold(
            jurisdictionCode: 'US-CA',
            revenueThreshold: Money::of('500000.00', 'USD'),
            effectiveFrom: new \DateTimeImmutable('2024-12-31'),
            effectiveTo: new \DateTimeImmutable('2024-01-01'), // Before effectiveFrom!
        );
    }

    public function test_it_checks_revenue_threshold_exceeded(): void
    {
        $threshold = new NexusThreshold(
            jurisdictionCode: 'US-CA',
            revenueThreshold: Money::of('500000.00', 'USD'),
        );

        $this->assertTrue($threshold->isRevenueExceeded(Money::of('600000.00', 'USD')));
        $this->assertTrue($threshold->isRevenueExceeded(Money::of('500000.00', 'USD'))); // Equal is exceeded
        $this->assertFalse($threshold->isRevenueExceeded(Money::of('400000.00', 'USD')));
    }

    public function test_it_checks_transaction_count_exceeded(): void
    {
        $threshold = new NexusThreshold(
            jurisdictionCode: 'US-CA',
            revenueThreshold: Money::of('500000.00', 'USD'),
            transactionThreshold: 200,
        );

        $this->assertTrue($threshold->isTransactionCountExceeded(250));
        $this->assertTrue($threshold->isTransactionCountExceeded(200)); // Equal is exceeded
        $this->assertFalse($threshold->isTransactionCountExceeded(150));
    }
}
