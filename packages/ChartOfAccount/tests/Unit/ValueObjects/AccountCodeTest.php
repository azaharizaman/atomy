<?php

declare(strict_types=1);

namespace Nexus\ChartOfAccount\Tests\Unit\ValueObjects;

use Nexus\ChartOfAccount\ValueObjects\AccountCode;
use Nexus\ChartOfAccount\Exceptions\InvalidAccountException;
use PHPUnit\Framework\TestCase;

final class AccountCodeTest extends TestCase
{
    public function test_fromString_accepts_numeric_code(): void
    {
        $code = AccountCode::fromString('1000');

        $this->assertSame('1000', $code->getValue());
    }

    public function test_fromString_accepts_alphanumeric_code(): void
    {
        $code = AccountCode::fromString('CASH1000');

        $this->assertSame('CASH1000', $code->getValue());
    }

    public function test_fromString_accepts_dash_separated_code(): void
    {
        $code = AccountCode::fromString('1000-001');

        $this->assertSame('1000-001', $code->getValue());
    }

    public function test_fromString_accepts_dot_separated_code(): void
    {
        $code = AccountCode::fromString('1000.001');

        $this->assertSame('1000.001', $code->getValue());
    }

    public function test_fromString_trims_whitespace(): void
    {
        $code = AccountCode::fromString('  1000  ');

        $this->assertSame('1000', $code->getValue());
    }

    public function test_fromString_throws_exception_for_empty_string(): void
    {
        $this->expectException(InvalidAccountException::class);
        $this->expectExceptionMessage('Account code cannot be empty');
        AccountCode::fromString('');
    }

    public function test_fromString_throws_exception_for_whitespace_only(): void
    {
        $this->expectException(InvalidAccountException::class);
        AccountCode::fromString('   ');
    }

    public function test_fromString_throws_exception_for_exceeding_max_length(): void
    {
        $longCode = str_repeat('A', 51);

        $this->expectException(InvalidAccountException::class);
        $this->expectExceptionMessage('Account code cannot exceed 50 characters');
        AccountCode::fromString($longCode);
    }

    public function test_fromString_throws_exception_for_invalid_characters(): void
    {
        $this->expectException(InvalidAccountException::class);
        $this->expectExceptionMessage('Account code must be alphanumeric, optionally separated by dots or dashes');
        AccountCode::fromString('1000@001');
    }

    public function test_fromString_throws_exception_for_consecutive_separators(): void
    {
        $this->expectException(InvalidAccountException::class);
        AccountCode::fromString('1000--001');
    }

    public function test_getLevel_returns_one_for_single_segment(): void
    {
        $code = AccountCode::fromString('1000');

        $this->assertSame(1, $code->getLevel());
    }

    public function test_getLevel_returns_two_for_dash_separated(): void
    {
        $code = AccountCode::fromString('1000-001');

        $this->assertSame(2, $code->getLevel());
    }

    public function test_getLevel_returns_three_for_multiple_segments(): void
    {
        $code = AccountCode::fromString('1000-001-01');

        $this->assertSame(3, $code->getLevel());
    }

    public function test_getSegments_returns_array_of_segments(): void
    {
        $code = AccountCode::fromString('1000-001-01');

        $this->assertSame(['1000', '001', '01'], $code->getSegments());
    }

    public function test_getSegments_returns_single_element_for_simple_code(): void
    {
        $code = AccountCode::fromString('1000');

        $this->assertSame(['1000'], $code->getSegments());
    }

    public function test_getParent_returns_null_for_top_level_code(): void
    {
        $code = AccountCode::fromString('1000');

        $this->assertNull($code->getParent());
    }

    public function test_getParent_returns_parent_for_two_level_code(): void
    {
        $code = AccountCode::fromString('1000-001');
        $parent = $code->getParent();

        $this->assertNotNull($parent);
        $this->assertSame('1000', $parent->getValue());
    }

    public function test_getParent_returns_parent_for_three_level_code(): void
    {
        $code = AccountCode::fromString('1000-001-01');
        $parent = $code->getParent();

        $this->assertNotNull($parent);
        $this->assertSame('1000-001', $parent->getValue());
    }

    public function test_getParent_preserves_separator_type(): void
    {
        $dotCode = AccountCode::fromString('1000.001');
        $dotParent = $dotCode->getParent();

        $this->assertNotNull($dotParent);
        $this->assertSame('1000', $dotParent->getValue());
    }

    public function test_isChildOf_returns_true_for_direct_child(): void
    {
        $parent = AccountCode::fromString('1000');
        $child = AccountCode::fromString('1000-001');

        $this->assertTrue($child->isChildOf($parent));
    }

    public function test_isChildOf_returns_true_for_deep_descendant(): void
    {
        $parent = AccountCode::fromString('1000');
        $descendant = AccountCode::fromString('1000-001-01');

        $this->assertTrue($descendant->isChildOf($parent));
    }

    public function test_isChildOf_returns_false_for_non_descendant(): void
    {
        $code1 = AccountCode::fromString('1000');
        $code2 = AccountCode::fromString('2000-001');

        $this->assertFalse($code2->isChildOf($code1));
    }

    public function test_isChildOf_returns_false_for_same_code(): void
    {
        $code = AccountCode::fromString('1000');

        $this->assertFalse($code->isChildOf($code));
    }

    public function test_toString_returns_value(): void
    {
        $code = AccountCode::fromString('1000-001');

        $this->assertSame('1000-001', (string) $code);
    }

    public function test_equals_returns_true_for_same_value(): void
    {
        $code1 = AccountCode::fromString('1000');
        $code2 = AccountCode::fromString('1000');

        $this->assertTrue($code1->equals($code2));
    }

    public function test_equals_returns_false_for_different_values(): void
    {
        $code1 = AccountCode::fromString('1000');
        $code2 = AccountCode::fromString('2000');

        $this->assertFalse($code1->equals($code2));
    }
}
