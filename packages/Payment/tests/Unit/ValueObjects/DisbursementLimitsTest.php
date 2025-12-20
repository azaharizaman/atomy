<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\LimitPeriod;
use Nexus\Payment\Exceptions\DisbursementLimitExceededException;
use Nexus\Payment\ValueObjects\DisbursementLimits;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DisbursementLimits::class)]
final class DisbursementLimitsTest extends TestCase
{
    #[Test]
    public function it_creates_no_limits(): void
    {
        $limits = DisbursementLimits::none();

        $this->assertFalse($limits->hasLimits());
        $this->assertNull($limits->getPerTransactionLimit());
        $this->assertNull($limits->getDailyLimit());
        $this->assertNull($limits->getWeeklyLimit());
        $this->assertNull($limits->getMonthlyLimit());
    }

    #[Test]
    public function it_creates_from_config_array(): void
    {
        $config = [
            'per_transaction' => ['amount' => 10000, 'currency' => 'USD'],
            'daily' => ['amount' => 50000, 'currency' => 'USD'],
            'weekly' => ['amount' => 200000, 'currency' => 'USD'],
            'monthly' => ['amount' => 500000, 'currency' => 'USD'],
        ];

        $limits = DisbursementLimits::fromConfig($config);

        $this->assertTrue($limits->hasLimits());
        $this->assertEquals(Money::of(10000, 'USD'), $limits->getPerTransactionLimit());
        $this->assertEquals(Money::of(50000, 'USD'), $limits->getDailyLimit());
        $this->assertEquals(Money::of(200000, 'USD'), $limits->getWeeklyLimit());
        $this->assertEquals(Money::of(500000, 'USD'), $limits->getMonthlyLimit());
    }

    #[Test]
    public function it_creates_with_per_transaction_limit(): void
    {
        $limit = Money::of(5000, 'USD');
        $limits = DisbursementLimits::none()->withPerTransactionLimit($limit);

        $this->assertTrue($limits->hasLimits());
        $this->assertEquals($limit, $limits->getPerTransactionLimit());
    }

    #[Test]
    public function it_creates_with_daily_limit(): void
    {
        $limit = Money::of(25000, 'USD');
        $limits = DisbursementLimits::none()->withDailyLimit($limit);

        $this->assertTrue($limits->hasLimits());
        $this->assertEquals($limit, $limits->getDailyLimit());
    }

    #[Test]
    public function it_creates_with_weekly_limit(): void
    {
        $limit = Money::of(100000, 'USD');
        $limits = DisbursementLimits::none()->withWeeklyLimit($limit);

        $this->assertTrue($limits->hasLimits());
        $this->assertEquals($limit, $limits->getWeeklyLimit());
    }

    #[Test]
    public function it_creates_with_monthly_limit(): void
    {
        $limit = Money::of(300000, 'USD');
        $limits = DisbursementLimits::none()->withMonthlyLimit($limit);

        $this->assertTrue($limits->hasLimits());
        $this->assertEquals($limit, $limits->getMonthlyLimit());
    }

    #[Test]
    public function it_chains_limit_methods(): void
    {
        $limits = DisbursementLimits::none()
            ->withPerTransactionLimit(Money::of(1000, 'USD'))
            ->withDailyLimit(Money::of(5000, 'USD'))
            ->withWeeklyLimit(Money::of(20000, 'USD'))
            ->withMonthlyLimit(Money::of(50000, 'USD'));

        $this->assertTrue($limits->hasLimits());
        $this->assertEquals(Money::of(1000, 'USD'), $limits->getPerTransactionLimit());
        $this->assertEquals(Money::of(5000, 'USD'), $limits->getDailyLimit());
        $this->assertEquals(Money::of(20000, 'USD'), $limits->getWeeklyLimit());
        $this->assertEquals(Money::of(50000, 'USD'), $limits->getMonthlyLimit());
    }

    #[Test]
    public function it_validates_amount_within_limit(): void
    {
        $limits = DisbursementLimits::none()
            ->withPerTransactionLimit(Money::of(10000, 'USD'));

        // Should not throw
        $limits->validateAmount(Money::of(5000, 'USD'));
        $limits->validateAmount(Money::of(10000, 'USD'));

        $this->assertTrue(true);
    }

    #[Test]
    public function it_throws_when_amount_exceeds_per_transaction_limit(): void
    {
        $limits = DisbursementLimits::none()
            ->withPerTransactionLimit(Money::of(10000, 'USD'));

        $this->expectException(DisbursementLimitExceededException::class);
        $this->expectExceptionMessage('per-transaction limit');

        $limits->validateAmount(Money::of(15000, 'USD'));
    }

    #[Test]
    public function it_validates_period_amount(): void
    {
        $limits = DisbursementLimits::none()
            ->withDailyLimit(Money::of(50000, 'USD'));

        $currentUsage = Money::of(30000, 'USD');
        $newAmount = Money::of(15000, 'USD');

        // 30000 + 15000 = 45000 < 50000, should not throw
        $limits->validatePeriodAmount($newAmount, $currentUsage, LimitPeriod::DAILY);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_throws_when_period_amount_exceeds_limit(): void
    {
        $limits = DisbursementLimits::none()
            ->withDailyLimit(Money::of(50000, 'USD'));

        $currentUsage = Money::of(40000, 'USD');
        $newAmount = Money::of(15000, 'USD');

        $this->expectException(DisbursementLimitExceededException::class);
        $this->expectExceptionMessage('Daily disbursement limit exceeded');

        // 40000 + 15000 = 55000 > 50000
        $limits->validatePeriodAmount($newAmount, $currentUsage, LimitPeriod::DAILY);
    }

    #[Test]
    public function it_gets_limit_for_period(): void
    {
        $limits = DisbursementLimits::none()
            ->withPerTransactionLimit(Money::of(1000, 'USD'))
            ->withDailyLimit(Money::of(5000, 'USD'))
            ->withWeeklyLimit(Money::of(20000, 'USD'))
            ->withMonthlyLimit(Money::of(50000, 'USD'));

        $this->assertEquals(
            Money::of(1000, 'USD'),
            $limits->getLimitForPeriod(LimitPeriod::PER_TRANSACTION)
        );
        $this->assertEquals(
            Money::of(5000, 'USD'),
            $limits->getLimitForPeriod(LimitPeriod::DAILY)
        );
        $this->assertEquals(
            Money::of(20000, 'USD'),
            $limits->getLimitForPeriod(LimitPeriod::WEEKLY)
        );
        $this->assertEquals(
            Money::of(50000, 'USD'),
            $limits->getLimitForPeriod(LimitPeriod::MONTHLY)
        );
    }

    #[Test]
    public function it_creates_with_count_limits(): void
    {
        $limits = DisbursementLimits::none()
            ->withDailyCountLimit(10)
            ->withWeeklyCountLimit(50)
            ->withMonthlyCountLimit(150);

        $this->assertTrue($limits->hasLimits());
        $this->assertSame(10, $limits->getCountLimitForPeriod(LimitPeriod::DAILY));
        $this->assertSame(50, $limits->getCountLimitForPeriod(LimitPeriod::WEEKLY));
        $this->assertSame(150, $limits->getCountLimitForPeriod(LimitPeriod::MONTHLY));
    }

    #[Test]
    public function it_validates_period_count(): void
    {
        $limits = DisbursementLimits::none()
            ->withDailyCountLimit(10);

        // Should not throw
        $limits->validatePeriodCount(5, LimitPeriod::DAILY);
        $limits->validatePeriodCount(9, LimitPeriod::DAILY);

        $this->assertTrue(true);
    }

    #[Test]
    public function it_throws_when_period_count_exceeds_limit(): void
    {
        $limits = DisbursementLimits::none()
            ->withDailyCountLimit(10);

        $this->expectException(DisbursementLimitExceededException::class);
        $this->expectExceptionMessage('count limit');

        $limits->validatePeriodCount(10, LimitPeriod::DAILY);
    }

    #[Test]
    public function it_serializes_to_array(): void
    {
        $limits = DisbursementLimits::none()
            ->withPerTransactionLimit(Money::of(1000, 'USD'))
            ->withDailyLimit(Money::of(5000, 'USD'))
            ->withDailyCountLimit(10);

        $array = $limits->toArray();

        $this->assertEquals(1000, $array['per_transaction']['amount']);
        $this->assertSame('USD', $array['per_transaction']['currency']);
        $this->assertEquals(5000, $array['daily']['amount']);
        $this->assertSame('USD', $array['daily']['currency']);
        $this->assertSame(10, $array['daily_count']);
    }

    #[Test]
    public function it_deserializes_from_array(): void
    {
        $data = [
            'per_transaction' => ['amount' => 1000, 'currency' => 'USD'],
            'daily' => ['amount' => 5000, 'currency' => 'USD'],
            'weekly' => ['amount' => 20000, 'currency' => 'USD'],
            'monthly' => ['amount' => 50000, 'currency' => 'USD'],
            'daily_count' => 10,
            'weekly_count' => 50,
            'monthly_count' => 150,
        ];

        $limits = DisbursementLimits::fromArray($data);

        $this->assertEquals(Money::of(1000, 'USD'), $limits->getPerTransactionLimit());
        $this->assertEquals(Money::of(5000, 'USD'), $limits->getDailyLimit());
        $this->assertEquals(Money::of(20000, 'USD'), $limits->getWeeklyLimit());
        $this->assertEquals(Money::of(50000, 'USD'), $limits->getMonthlyLimit());
        $this->assertSame(10, $limits->getCountLimitForPeriod(LimitPeriod::DAILY));
        $this->assertSame(50, $limits->getCountLimitForPeriod(LimitPeriod::WEEKLY));
        $this->assertSame(150, $limits->getCountLimitForPeriod(LimitPeriod::MONTHLY));
    }
}
