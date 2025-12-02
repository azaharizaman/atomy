<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use DateTimeImmutable;
use Nexus\Common\Exceptions\InvalidValueException;
use Nexus\Common\ValueObjects\Percentage;
use PHPUnit\Framework\TestCase;

final class PercentageTest extends TestCase
{
    public function test_of_creates_percentage(): void
    {
        $percentage = new Percentage(15.5);
        
        $this->assertSame(15.5, $percentage->getValue());
    }

    public function test_from_decimal_creates_percentage(): void
    {
        $percentage = Percentage::fromDecimal(0.15);
        
        $this->assertSame(15.0, $percentage->getValue());
    }

    public function test_throws_exception_for_negative_value(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Percentage must be between 0 and 100');
        
        new Percentage(-5.0);
    }

    public function test_throws_exception_for_value_above_100(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Percentage must be between 0 and 100');
        
        new Percentage(101.0);
    }

    public function test_as_decimal_returns_decimal_value(): void
    {
        $percentage = new Percentage(25.0);
        
        $this->assertSame(0.25, $percentage->asDecimal());
    }

    public function test_of_calculates_percentage_of_amount(): void
    {
        $percentage = new Percentage(20.0);
        
        $this->assertSame(40.0, $percentage->of(200.0));
    }

    public function test_add_returns_new_percentage(): void
    {
        $p1 = new Percentage(15.0);
        $p2 = new Percentage(5.0);
        
        $result = $p1->add($p2);
        
        $this->assertSame(20.0, $result->getValue());
        $this->assertSame(15.0, $p1->getValue()); // Original unchanged
    }

    public function test_add_throws_exception_when_result_exceeds_100(): void
    {
        $this->expectException(InvalidValueException::class);
        
        $p1 = new Percentage(60.0);
        $p2 = new Percentage(50.0);
        $p1->add($p2);
    }

    public function test_subtract_returns_new_percentage(): void
    {
        $p1 = new Percentage(25.0);
        $p2 = new Percentage(10.0);
        
        $result = $p1->subtract($p2);
        
        $this->assertSame(15.0, $result->getValue());
    }

    public function test_subtract_throws_exception_when_result_is_negative(): void
    {
        $this->expectException(InvalidValueException::class);
        
        $p1 = new Percentage(10.0);
        $p2 = new Percentage(20.0);
        $p1->subtract($p2);
    }

    public function test_multiply_returns_new_percentage(): void
    {
        $percentage = new Percentage(10.0);
        
        $result = $percentage->multiply(2.5);
        
        $this->assertSame(25.0, $result->getValue());
    }

    public function test_divide_returns_new_percentage(): void
    {
        $percentage = new Percentage(50.0);
        
        $result = $percentage->divide(2);
        
        $this->assertSame(25.0, $result->getValue());
    }

    public function test_divide_throws_exception_for_zero(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Cannot divide by zero');
        
        $percentage = new Percentage(50.0);
        $percentage->divide(0);
    }

    public function test_compare_to(): void
    {
        $p1 = new Percentage(15.0);
        $p2 = new Percentage(20.0);
        $p3 = new Percentage(15.0);
        
        $this->assertSame(-1, $p1->compareTo($p2));
        $this->assertSame(1, $p2->compareTo($p1));
        $this->assertSame(0, $p1->compareTo($p3));
    }

    public function test_equals(): void
    {
        $p1 = new Percentage(15.5);
        $p2 = new Percentage(15.5);
        $p3 = new Percentage(20.0);
        
        $this->assertTrue($p1->equals($p2));
        $this->assertFalse($p1->equals($p3));
    }

    public function test_greater_than(): void
    {
        $p1 = new Percentage(20.0);
        $p2 = new Percentage(15.0);
        
        $this->assertTrue($p1->greaterThan($p2));
        $this->assertFalse($p2->greaterThan($p1));
    }

    public function test_less_than(): void
    {
        $p1 = new Percentage(15.0);
        $p2 = new Percentage(20.0);
        
        $this->assertTrue($p1->lessThan($p2));
        $this->assertFalse($p2->lessThan($p1));
    }

    public function test_format_with_default_options(): void
    {
        $percentage = new Percentage(15.5);
        
        $this->assertSame('15.50%', $percentage->format());
    }

    public function test_format_with_custom_decimals(): void
    {
        $percentage = new Percentage(15.555);
        
        $this->assertSame('15.6%', $percentage->format(['decimals' => 1]));
    }

    public function test_to_array(): void
    {
        $percentage = new Percentage(25.5);
        
        $expected = ['value' => 25.5];
        $this->assertSame($expected, $percentage->toArray());
    }

    public function test_to_string(): void
    {
        $percentage = new Percentage(18.75);
        
        $this->assertSame('18.75%', $percentage->toString());
    }

    public function test_from_array(): void
    {
        $data = ['value' => 33.33];
        
        $percentage = Percentage::fromArray($data);
        
        $this->assertSame(33.33, $percentage->getValue());
    }

    public function test_average_returns_average_percentage(): void
    {
        $percentages = [
            new Percentage(10.0),
            new Percentage(20.0),
            new Percentage(30.0),
        ];
        
        $avg = Percentage::average($percentages);
        
        $this->assertSame(20.0, $avg->getValue());
    }

    public function test_abs_returns_same_value_for_positive(): void
    {
        $percentage = new Percentage(15.0);
        
        $abs = $percentage->abs();
        
        $this->assertSame(15.0, $abs->getValue());
    }

    public function test_is_within_range(): void
    {
        $percentage = new Percentage(50.0);
        $min = new Percentage(40.0);
        $max = new Percentage(60.0);
        
        $this->assertTrue($percentage->isWithinRange($min, $max));
        
        $outside = new Percentage(70.0);
        $this->assertFalse($outside->isWithinRange($min, $max));
    }
}
