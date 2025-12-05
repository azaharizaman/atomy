<?php

declare(strict_types=1);

namespace Nexus\Common\Tests\Unit\ValueObjects;

use Nexus\Common\Exceptions\CurrencyMismatchException;
use Nexus\Common\Exceptions\InvalidMoneyException;
use Nexus\Common\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    // ========== Construction Tests ==========

    public function test_construct_with_valid_values_creates_money(): void
    {
        $money = new Money(10000, 'USD');

        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame(10000, $money->getAmountInMinorUnits());
        $this->assertSame('USD', $money->getCurrency());
    }

    public function test_construct_with_lowercase_currency_converts_to_uppercase(): void
    {
        $money = new Money(5000, 'usd');

        $this->assertSame('USD', $money->getCurrency());
    }

    public function test_construct_with_zero_amount_creates_valid_money(): void
    {
        $money = new Money(0, 'EUR');

        $this->assertSame(0, $money->getAmountInMinorUnits());
        $this->assertTrue($money->isZero());
    }

    public function test_construct_with_negative_amount_creates_valid_money(): void
    {
        $money = new Money(-15000, 'MYR');

        $this->assertSame(-15000, $money->getAmountInMinorUnits());
        $this->assertTrue($money->isNegative());
    }

    public function test_construct_with_invalid_currency_code_throws_exception(): void
    {
        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Currency must be 3-character ISO 4217 code');

        new Money(1000, 'US');
    }

    public function test_construct_with_empty_currency_throws_exception(): void
    {
        $this->expectException(InvalidMoneyException::class);

        new Money(1000, '');
    }

    public function test_construct_with_too_long_currency_throws_exception(): void
    {
        $this->expectException(InvalidMoneyException::class);

        new Money(1000, 'USDD');
    }

    // ========== Static Factory Tests ==========

    public function test_of_with_float_creates_money(): void
    {
        $money = Money::of(100.50, 'USD');

        $this->assertSame(10050, $money->getAmountInMinorUnits());
        $this->assertSame('USD', $money->getCurrency());
    }

    public function test_of_with_string_creates_money(): void
    {
        $money = Money::of('99.99', 'MYR');

        $this->assertSame(9999, $money->getAmountInMinorUnits());
        $this->assertSame('MYR', $money->getCurrency());
    }

    public function test_of_with_custom_scale_creates_money(): void
    {
        $money = Money::of(0.12345678, 'BTC', 8);

        $this->assertSame(12345678, $money->getAmountInMinorUnits());
    }

    public function test_of_with_zero_scale_creates_money(): void
    {
        $money = Money::of(1000, 'JPY', 0);

        $this->assertSame(1000, $money->getAmountInMinorUnits());
    }

    public function test_of_rounds_half_up(): void
    {
        $money = Money::of(100.555, 'USD');

        $this->assertSame(10056, $money->getAmountInMinorUnits());
    }

    public function test_zero_creates_zero_money(): void
    {
        $money = Money::zero('EUR');

        $this->assertSame(0, $money->getAmountInMinorUnits());
        $this->assertTrue($money->isZero());
        $this->assertSame('EUR', $money->getCurrency());
    }

    // ========== Getter Tests ==========

    public function test_get_amount_returns_decimal_value(): void
    {
        $money = new Money(12345, 'USD');

        $this->assertSame(123.45, $money->getAmount());
    }

    public function test_get_amount_with_custom_scale_returns_correct_value(): void
    {
        $money = new Money(123456789, 'BTC');

        $this->assertSame(1234567.89, $money->getAmount(2));
        $this->assertSame(1234.56789, $money->getAmount(5));
    }

    public function test_format_returns_formatted_string(): void
    {
        $money = new Money(123456, 'USD');

        $this->assertSame('1234.56', $money->format());
    }

    public function test_format_with_custom_decimals_returns_formatted_string(): void
    {
        $money = new Money(123456, 'USD');

        $this->assertSame('1234.560', $money->format(3));
        $this->assertSame('1235', $money->format(0));
    }

    public function test_to_string_returns_string_representation(): void
    {
        $money = Money::of(1234.56, 'USD');

        $this->assertSame('1234.56 USD', $money->toString());
    }

    public function test_to_array_returns_array_representation(): void
    {
        $money = Money::of(999.99, 'MYR');

        $this->assertSame([
            'amount' => 999.99,
            'currency' => 'MYR',
        ], $money->toArray());
    }

    // ========== Addition Tests ==========

    public function test_add_returns_sum_of_amounts(): void
    {
        $money1 = Money::of(100.00, 'USD');
        $money2 = Money::of(50.00, 'USD');

        $result = $money1->add($money2);

        $this->assertSame(15000, $result->getAmountInMinorUnits());
        $this->assertSame(150.00, $result->getAmount());
    }

    public function test_add_with_negative_amount_subtracts(): void
    {
        $money1 = Money::of(100.00, 'USD');
        $money2 = Money::of(-30.00, 'USD');

        $result = $money1->add($money2);

        $this->assertSame(70.00, $result->getAmount());
    }

    public function test_add_with_zero_returns_same_amount(): void
    {
        $money1 = Money::of(100.00, 'EUR');
        $money2 = Money::zero('EUR');

        $result = $money1->add($money2);

        $this->assertSame(100.00, $result->getAmount());
    }

    public function test_add_with_different_currency_throws_exception(): void
    {
        $usd = Money::of(100.00, 'USD');
        $eur = Money::of(100.00, 'EUR');

        $this->expectException(CurrencyMismatchException::class);
        $this->expectExceptionMessage('Currency mismatch: USD vs EUR');

        $usd->add($eur);
    }

    public function test_add_does_not_mutate_original(): void
    {
        $original = Money::of(100.00, 'USD');
        $other = Money::of(50.00, 'USD');

        $result = $original->add($other);

        $this->assertSame(100.00, $original->getAmount());
        $this->assertSame(150.00, $result->getAmount());
        $this->assertNotSame($original, $result);
    }

    // ========== Subtraction Tests ==========

    public function test_subtract_returns_difference(): void
    {
        $money1 = Money::of(100.00, 'USD');
        $money2 = Money::of(30.00, 'USD');

        $result = $money1->subtract($money2);

        $this->assertSame(70.00, $result->getAmount());
    }

    public function test_subtract_resulting_in_negative_returns_negative_money(): void
    {
        $money1 = Money::of(50.00, 'USD');
        $money2 = Money::of(100.00, 'USD');

        $result = $money1->subtract($money2);

        $this->assertSame(-50.00, $result->getAmount());
        $this->assertTrue($result->isNegative());
    }

    public function test_subtract_with_different_currency_throws_exception(): void
    {
        $usd = Money::of(100.00, 'USD');
        $myr = Money::of(50.00, 'MYR');

        $this->expectException(CurrencyMismatchException::class);

        $usd->subtract($myr);
    }

    // ========== Multiplication Tests ==========

    public function test_multiply_by_integer_returns_correct_result(): void
    {
        $money = Money::of(100.00, 'USD');

        $result = $money->multiply(3);

        $this->assertSame(300.00, $result->getAmount());
    }

    public function test_multiply_by_float_returns_rounded_result(): void
    {
        $money = Money::of(100.00, 'USD');

        $result = $money->multiply(1.5);

        $this->assertSame(150.00, $result->getAmount());
    }

    public function test_multiply_by_zero_returns_zero(): void
    {
        $money = Money::of(100.00, 'USD');

        $result = $money->multiply(0);

        $this->assertTrue($result->isZero());
    }

    public function test_multiply_by_negative_returns_negative_result(): void
    {
        $money = Money::of(100.00, 'USD');

        $result = $money->multiply(-2);

        $this->assertSame(-200.00, $result->getAmount());
        $this->assertTrue($result->isNegative());
    }

    public function test_multiply_rounds_correctly(): void
    {
        $money = Money::of(10.00, 'USD');

        $result = $money->multiply(0.333333);

        $this->assertSame(3.33, $result->getAmount());
    }

    // ========== Division Tests ==========

    public function test_divide_returns_correct_result(): void
    {
        $money = Money::of(100.00, 'USD');

        $result = $money->divide(4);

        $this->assertSame(25.00, $result->getAmount());
    }

    public function test_divide_with_float_returns_rounded_result(): void
    {
        $money = Money::of(100.00, 'USD');

        $result = $money->divide(3);

        $this->assertSame(33.33, $result->getAmount());
    }

    public function test_divide_by_zero_throws_exception(): void
    {
        $money = Money::of(100.00, 'USD');

        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Cannot divide by zero');

        $money->divide(0);
    }

    public function test_divide_by_negative_returns_negative_result(): void
    {
        $money = Money::of(100.00, 'USD');

        $result = $money->divide(-2);

        $this->assertSame(-50.00, $result->getAmount());
    }

    // ========== Absolute Value Tests ==========

    public function test_abs_with_positive_returns_same_value(): void
    {
        $money = Money::of(100.00, 'USD');

        $result = $money->abs();

        $this->assertSame(100.00, $result->getAmount());
    }

    public function test_abs_with_negative_returns_positive(): void
    {
        $money = Money::of(-100.00, 'USD');

        $result = $money->abs();

        $this->assertSame(100.00, $result->getAmount());
        $this->assertTrue($result->isPositive());
    }

    public function test_abs_with_zero_returns_zero(): void
    {
        $money = Money::zero('EUR');

        $result = $money->abs();

        $this->assertTrue($result->isZero());
    }

    // ========== Negation Tests ==========

    public function test_negate_positive_returns_negative(): void
    {
        $money = Money::of(100.00, 'USD');

        $result = $money->negate();

        $this->assertSame(-100.00, $result->getAmount());
        $this->assertTrue($result->isNegative());
    }

    public function test_negate_negative_returns_positive(): void
    {
        $money = Money::of(-100.00, 'USD');

        $result = $money->negate();

        $this->assertSame(100.00, $result->getAmount());
        $this->assertTrue($result->isPositive());
    }

    public function test_negate_zero_returns_zero(): void
    {
        $money = Money::zero('MYR');

        $result = $money->negate();

        $this->assertTrue($result->isZero());
    }

    // ========== State Check Tests ==========

    public function test_is_positive_returns_true_for_positive_amount(): void
    {
        $money = Money::of(100.00, 'USD');

        $this->assertTrue($money->isPositive());
    }

    public function test_is_positive_returns_false_for_negative_amount(): void
    {
        $money = Money::of(-100.00, 'USD');

        $this->assertFalse($money->isPositive());
    }

    public function test_is_positive_returns_false_for_zero(): void
    {
        $money = Money::zero('EUR');

        $this->assertFalse($money->isPositive());
    }

    public function test_is_negative_returns_true_for_negative_amount(): void
    {
        $money = Money::of(-50.00, 'MYR');

        $this->assertTrue($money->isNegative());
    }

    public function test_is_negative_returns_false_for_positive_amount(): void
    {
        $money = Money::of(50.00, 'MYR');

        $this->assertFalse($money->isNegative());
    }

    public function test_is_negative_returns_false_for_zero(): void
    {
        $money = Money::zero('GBP');

        $this->assertFalse($money->isNegative());
    }

    public function test_is_zero_returns_true_for_zero_amount(): void
    {
        $money = Money::zero('USD');

        $this->assertTrue($money->isZero());
    }

    public function test_is_zero_returns_false_for_non_zero_amount(): void
    {
        $money = Money::of(0.01, 'USD');

        $this->assertFalse($money->isZero());
    }

    // ========== Comparison Tests ==========

    public function test_compare_to_returns_negative_one_when_less_than(): void
    {
        $money1 = Money::of(50.00, 'USD');
        $money2 = Money::of(100.00, 'USD');

        $this->assertSame(-1, $money1->compareTo($money2));
    }

    public function test_compare_to_returns_zero_when_equal(): void
    {
        $money1 = Money::of(100.00, 'USD');
        $money2 = Money::of(100.00, 'USD');

        $this->assertSame(0, $money1->compareTo($money2));
    }

    public function test_compare_to_returns_positive_one_when_greater_than(): void
    {
        $money1 = Money::of(150.00, 'USD');
        $money2 = Money::of(100.00, 'USD');

        $this->assertSame(1, $money1->compareTo($money2));
    }

    public function test_compare_to_with_different_currency_throws_exception(): void
    {
        $usd = Money::of(100.00, 'USD');
        $eur = Money::of(100.00, 'EUR');

        $this->expectException(CurrencyMismatchException::class);

        $usd->compareTo($eur);
    }

    public function test_equals_returns_true_for_same_amount(): void
    {
        $money1 = Money::of(100.00, 'USD');
        $money2 = Money::of(100.00, 'USD');

        $this->assertTrue($money1->equals($money2));
    }

    public function test_equals_returns_false_for_different_amount(): void
    {
        $money1 = Money::of(100.00, 'USD');
        $money2 = Money::of(100.01, 'USD');

        $this->assertFalse($money1->equals($money2));
    }

    public function test_greater_than_returns_true_when_greater(): void
    {
        $money1 = Money::of(150.00, 'MYR');
        $money2 = Money::of(100.00, 'MYR');

        $this->assertTrue($money1->greaterThan($money2));
    }

    public function test_greater_than_returns_false_when_equal_or_less(): void
    {
        $money1 = Money::of(100.00, 'MYR');
        $money2 = Money::of(100.00, 'MYR');
        $money3 = Money::of(150.00, 'MYR');

        $this->assertFalse($money1->greaterThan($money2));
        $this->assertFalse($money1->greaterThan($money3));
    }

    public function test_greater_than_or_equal_returns_true_when_greater_or_equal(): void
    {
        $money1 = Money::of(150.00, 'EUR');
        $money2 = Money::of(100.00, 'EUR');
        $money3 = Money::of(150.00, 'EUR');

        $this->assertTrue($money1->greaterThanOrEqual($money2));
        $this->assertTrue($money1->greaterThanOrEqual($money3));
    }

    public function test_greater_than_or_equal_returns_false_when_less(): void
    {
        $money1 = Money::of(50.00, 'EUR');
        $money2 = Money::of(100.00, 'EUR');

        $this->assertFalse($money1->greaterThanOrEqual($money2));
    }

    public function test_less_than_returns_true_when_less(): void
    {
        $money1 = Money::of(50.00, 'GBP');
        $money2 = Money::of(100.00, 'GBP');

        $this->assertTrue($money1->lessThan($money2));
    }

    public function test_less_than_returns_false_when_equal_or_greater(): void
    {
        $money1 = Money::of(100.00, 'GBP');
        $money2 = Money::of(100.00, 'GBP');
        $money3 = Money::of(50.00, 'GBP');

        $this->assertFalse($money1->lessThan($money2));
        $this->assertFalse($money1->lessThan($money3));
    }

    public function test_less_than_or_equal_returns_true_when_less_or_equal(): void
    {
        $money1 = Money::of(50.00, 'JPY');
        $money2 = Money::of(100.00, 'JPY');
        $money3 = Money::of(50.00, 'JPY');

        $this->assertTrue($money1->lessThanOrEqual($money2));
        $this->assertTrue($money1->lessThanOrEqual($money3));
    }

    public function test_less_than_or_equal_returns_false_when_greater(): void
    {
        $money1 = Money::of(150.00, 'JPY');
        $money2 = Money::of(100.00, 'JPY');

        $this->assertFalse($money1->lessThanOrEqual($money2));
    }

    // ========== Allocation Tests ==========

    public function test_allocate_with_equal_ratios_distributes_evenly(): void
    {
        $money = Money::of(100.00, 'USD');

        $shares = $money->allocate([1, 1, 1]);

        $this->assertCount(3, $shares);
        $this->assertSame(33.34, $shares[0]->getAmount());
        $this->assertSame(33.33, $shares[1]->getAmount());
        $this->assertSame(33.33, $shares[2]->getAmount());
    }

    public function test_allocate_with_weighted_ratios_distributes_proportionally(): void
    {
        $money = Money::of(100.00, 'USD');

        $shares = $money->allocate([60, 30, 10]);

        $this->assertSame(60.00, $shares[0]->getAmount());
        $this->assertSame(30.00, $shares[1]->getAmount());
        $this->assertSame(10.00, $shares[2]->getAmount());
    }

    public function test_allocate_distributes_remainder_to_first_shares(): void
    {
        $money = Money::of(10.00, 'USD');

        $shares = $money->allocate([1, 1, 1]);

        $this->assertSame(3.34, $shares[0]->getAmount());
        $this->assertSame(3.33, $shares[1]->getAmount());
        $this->assertSame(3.33, $shares[2]->getAmount());
    }

    public function test_allocate_preserves_total_amount(): void
    {
        $money = Money::of(123.45, 'MYR');

        $shares = $money->allocate([2, 3, 5]);

        $total = $shares[0]->add($shares[1])->add($shares[2]);
        $this->assertSame(123.45, $total->getAmount());
    }

    public function test_allocate_with_empty_ratios_throws_exception(): void
    {
        $money = Money::of(100.00, 'USD');

        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Cannot allocate to empty ratios');

        $money->allocate([]);
    }

    public function test_allocate_with_zero_sum_ratios_throws_exception(): void
    {
        $money = Money::of(100.00, 'USD');

        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Sum of ratios must be positive');

        $money->allocate([0, 0, 0]);
    }

    public function test_allocate_with_negative_ratios_throws_exception(): void
    {
        $money = Money::of(100.00, 'USD');

        $this->expectException(InvalidMoneyException::class);

        $money->allocate([-1, 1, 1]);
    }

    // ========== Currency Conversion Tests ==========

    public function test_convert_to_currency_with_float_rate_converts_correctly(): void
    {
        $money = Money::of(100.00, 'USD');

        $result = $money->convertToCurrency('EUR', 0.85);

        $this->assertSame('EUR', $result->getCurrency());
        $this->assertSame(8500, $result->getAmountInMinorUnits());
        $this->assertSame(85.00, $result->getAmount());
    }

    public function test_convert_to_currency_with_string_rate_maintains_precision(): void
    {
        $money = Money::of(100.00, 'USD');

        // High-precision rate that would lose precision as float
        $result = $money->convertToCurrencyWithStringRate('EUR', '0.85123456', 8);

        $this->assertSame('EUR', $result->getCurrency());
        $this->assertSame(8512, $result->getAmountInMinorUnits());
        $this->assertSame(85.12, $result->getAmount());
    }

    public function test_convert_to_currency_with_string_rate_handles_very_small_rates(): void
    {
        $money = Money::of(1000000.00, 'USD'); // 1 million USD

        // Very small rate with high precision
        $result = $money->convertToCurrencyWithStringRate('BTC', '0.00001234', 8);

        $this->assertSame('BTC', $result->getCurrency());
        $this->assertSame(1234, $result->getAmountInMinorUnits());
    }

    public function test_convert_to_currency_with_string_rate_handles_large_rates(): void
    {
        $money = Money::of(1.00, 'USD'); // 100 minor units

        // Large rate: 100 * 150.123456 = 15012.3456 minor units, rounds to 15012
        $result = $money->convertToCurrencyWithStringRate('JPY', '150.123456', 8);

        $this->assertSame('JPY', $result->getCurrency());
        $this->assertSame(15012, $result->getAmountInMinorUnits());
    }

    public function test_convert_to_currency_with_string_rate_rounds_correctly(): void
    {
        $money = Money::of(100.00, 'USD');

        // Rate that requires rounding
        $result = $money->convertToCurrencyWithStringRate('EUR', '0.856789', 8);

        $this->assertSame('EUR', $result->getCurrency());
        $this->assertSame(8568, $result->getAmountInMinorUnits());
        $this->assertSame(85.68, $result->getAmount());
    }

    public function test_convert_to_currency_with_string_rate_default_scale(): void
    {
        $money = Money::of(100.00, 'USD');

        // Test default scale parameter
        $result = $money->convertToCurrencyWithStringRate('EUR', '0.85');

        $this->assertSame('EUR', $result->getCurrency());
        $this->assertSame(8500, $result->getAmountInMinorUnits());
    }

    public function test_convert_to_currency_with_string_rate_handles_negative_amounts(): void
    {
        $money = Money::of(-100.00, 'USD'); // -10000 minor units

        // Negative amount with rate
        $result = $money->convertToCurrencyWithStringRate('EUR', '0.856789', 8);

        // -10000 * 0.856789 = -8567.89, rounds to -8568
        $this->assertSame('EUR', $result->getCurrency());
        $this->assertSame(-8568, $result->getAmountInMinorUnits());
        $this->assertSame(-85.68, $result->getAmount());
    }

    public function test_convert_to_currency_with_string_rate_rounds_halfway_positive(): void
    {
        // Test "round half away from zero" for positive halfway value
        $money = Money::of(100.00, 'USD'); // 10000 minor units

        // Rate that produces exactly .5: 10000 * 0.85005 = 8500.5
        $rateProducingHalfway = '0.85005';
        $result = $money->convertToCurrencyWithStringRate('EUR', $rateProducingHalfway, 8);

        // 8500.5 should round to 8501 (away from zero)
        $this->assertSame(8501, $result->getAmountInMinorUnits());
    }

    public function test_convert_to_currency_with_string_rate_rounds_halfway_negative(): void
    {
        // Test "round half away from zero" for negative halfway value
        $money = Money::of(-100.00, 'USD'); // -10000 minor units

        // Rate that produces exactly .5: -10000 * 0.85005 = -8500.5
        $rateProducingHalfway = '0.85005';
        $result = $money->convertToCurrencyWithStringRate('EUR', $rateProducingHalfway, 8);

        // -8500.5 should round to -8501 (away from zero)
        $this->assertSame(-8501, $result->getAmountInMinorUnits());
    }

    public function test_convert_to_currency_with_string_rate_throws_on_invalid_exchange_rate(): void
    {
        $money = Money::of(100.00, 'USD');

        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Exchange rate must be numeric');

        $money->convertToCurrencyWithStringRate('EUR', 'invalid');
    }

    public function test_convert_to_currency_with_string_rate_throws_on_empty_exchange_rate(): void
    {
        $money = Money::of(100.00, 'USD');

        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Exchange rate must be numeric');

        $money->convertToCurrencyWithStringRate('EUR', '');
    }

    public function test_convert_to_currency_with_string_rate_throws_on_negative_exchange_rate(): void
    {
        $money = Money::of(100.00, 'USD');

        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Exchange rate must be positive');

        $money->convertToCurrencyWithStringRate('EUR', '-0.5');
    }

    public function test_convert_to_currency_with_string_rate_throws_on_zero_exchange_rate(): void
    {
        $money = Money::of(100.00, 'USD');

        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Exchange rate must be positive');

        $money->convertToCurrencyWithStringRate('EUR', '0');
    }

    public function test_convert_to_currency_with_string_rate_throws_on_negative_scale(): void
    {
        $money = Money::of(100.00, 'USD');

        $this->expectException(InvalidMoneyException::class);
        $this->expectExceptionMessage('Scale must be non-negative');

        $money->convertToCurrencyWithStringRate('EUR', '0.85', -1);
    }

    // ========== Immutability Tests ==========

    public function test_money_is_immutable(): void
    {
        $original = Money::of(100.00, 'USD');
        
        $added = $original->add(Money::of(50.00, 'USD'));
        $subtracted = $original->subtract(Money::of(50.00, 'USD'));
        $multiplied = $original->multiply(2);
        $divided = $original->divide(2);
        $negated = $original->negate();
        $absolute = $original->abs();

        $this->assertSame(100.00, $original->getAmount());
        $this->assertNotSame($original, $added);
        $this->assertNotSame($original, $subtracted);
        $this->assertNotSame($original, $multiplied);
        $this->assertNotSame($original, $divided);
        $this->assertNotSame($original, $negated);
        $this->assertNotSame($original, $absolute);
    }

    public function test_money_class_is_final(): void
    {
        $reflection = new \ReflectionClass(Money::class);

        $this->assertTrue($reflection->isFinal());
    }

    public function test_money_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(Money::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
