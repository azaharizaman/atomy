<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use Nexus\Common\Exceptions\InvalidValueException;
use Nexus\Common\ValueObjects\Period;
use PHPUnit\Framework\TestCase;

final class PeriodTest extends TestCase
{
    public function test_creates_valid_period(): void
    {
        $period = new Period('2024-Q1');
        
        $this->assertSame('2024-Q1', $period->getName());
    }

    public function test_throws_exception_for_empty_name(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Period name cannot be empty');
        
        new Period('');
    }

    public function test_throws_exception_for_whitespace_only_name(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Period name cannot be empty');
        
        new Period('   ');
    }

    public function test_throws_exception_for_name_exceeding_50_characters(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Period name cannot exceed 50 characters');
        
        new Period(str_repeat('a', 51));
    }

    public function test_for_month_creates_monthly_period(): void
    {
        $period = Period::forMonth(2024, 1);
        
        $this->assertSame('JAN-2024', $period->getName());
        $this->assertTrue($period->isMonthly());
        $this->assertFalse($period->isQuarterly());
        $this->assertFalse($period->isYearly());
    }

    public function test_for_month_validates_month_range(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Month must be between 1 and 12, got: 13');
        
        Period::forMonth(2024, 13);
    }

    public function test_for_month_validates_minimum_month(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Month must be between 1 and 12, got: 0');
        
        Period::forMonth(2024, 0);
    }

    public function test_for_month_creates_all_months_correctly(): void
    {
        $expected = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
        
        for ($month = 1; $month <= 12; $month++) {
            $period = Period::forMonth(2024, $month);
            $this->assertSame($expected[$month - 1] . '-2024', $period->getName());
        }
    }

    public function test_for_quarter_creates_quarterly_period(): void
    {
        $period = Period::forQuarter(2024, 1);
        
        $this->assertSame('2024-Q1', $period->getName());
        $this->assertTrue($period->isQuarterly());
        $this->assertFalse($period->isMonthly());
        $this->assertFalse($period->isYearly());
    }

    public function test_for_quarter_validates_quarter_range(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Quarter must be between 1 and 4, got: 5');
        
        Period::forQuarter(2024, 5);
    }

    public function test_for_quarter_validates_minimum_quarter(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Quarter must be between 1 and 4, got: 0');
        
        Period::forQuarter(2024, 0);
    }

    public function test_for_quarter_creates_all_quarters_correctly(): void
    {
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $period = Period::forQuarter(2024, $quarter);
            $this->assertSame("2024-Q{$quarter}", $period->getName());
        }
    }

    public function test_for_year_creates_yearly_period(): void
    {
        $period = Period::forYear(2024);
        
        $this->assertSame('FY2024', $period->getName());
        $this->assertTrue($period->isYearly());
        $this->assertFalse($period->isMonthly());
        $this->assertFalse($period->isQuarterly());
    }

    public function test_is_monthly_detects_monthly_format(): void
    {
        $monthly = new Period('DEC-2024');
        $notMonthly = new Period('2024-Q4');
        
        $this->assertTrue($monthly->isMonthly());
        $this->assertFalse($notMonthly->isMonthly());
    }

    public function test_is_quarterly_detects_quarterly_format(): void
    {
        $quarterly = new Period('2024-Q2');
        $notQuarterly = new Period('FY2024');
        
        $this->assertTrue($quarterly->isQuarterly());
        $this->assertFalse($notQuarterly->isQuarterly());
    }

    public function test_is_yearly_detects_yearly_format(): void
    {
        $yearly = new Period('FY2024');
        $notYearly = new Period('JAN-2024');
        
        $this->assertTrue($yearly->isYearly());
        $this->assertFalse($notYearly->isYearly());
    }

    public function test_equals_compares_periods_correctly(): void
    {
        $period1 = new Period('2024-Q1');
        $period2 = new Period('2024-Q1');
        $period3 = new Period('2024-Q2');
        
        $this->assertTrue($period1->equals($period2));
        $this->assertFalse($period1->equals($period3));
    }

    public function test_compare_to_orders_periods(): void
    {
        $period1 = new Period('2024-Q1');
        $period2 = new Period('2024-Q2');
        
        $this->assertLessThan(0, $period1->compareTo($period2));
        $this->assertGreaterThan(0, $period2->compareTo($period1));
        $this->assertEquals(0, $period1->compareTo(new Period('2024-Q1')));
    }

    public function test_greater_than_compares_correctly(): void
    {
        $period1 = new Period('2024-Q2');
        $period2 = new Period('2024-Q1');
        
        $this->assertTrue($period1->greaterThan($period2));
        $this->assertFalse($period2->greaterThan($period1));
    }

    public function test_less_than_compares_correctly(): void
    {
        $period1 = new Period('2024-Q1');
        $period2 = new Period('2024-Q2');
        
        $this->assertTrue($period1->lessThan($period2));
        $this->assertFalse($period2->lessThan($period1));
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $period = Period::forQuarter(2024, 1);
        $array = $period->toArray();
        
        $this->assertIsArray($array);
        $this->assertSame('2024-Q1', $array['name']);
        $this->assertFalse($array['is_monthly']);
        $this->assertTrue($array['is_quarterly']);
        $this->assertFalse($array['is_yearly']);
    }

    public function test_to_string_returns_name(): void
    {
        $period = new Period('2024-Q1');
        
        $this->assertSame('2024-Q1', $period->toString());
    }

    public function test_from_array_creates_period(): void
    {
        $data = ['name' => 'FY2024'];
        $period = Period::fromArray($data);
        
        $this->assertSame('FY2024', $period->getName());
    }

    public function test_custom_period_names_are_allowed(): void
    {
        $period = new Period('H1-2024'); // Half-year
        
        $this->assertSame('H1-2024', $period->getName());
        $this->assertFalse($period->isMonthly());
        $this->assertFalse($period->isQuarterly());
        $this->assertFalse($period->isYearly());
    }
}
