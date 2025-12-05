<?php

declare(strict_types=1);

namespace Nexus\JournalEntry\Tests\Unit\ValueObjects;

use Nexus\JournalEntry\ValueObjects\JournalEntryNumber;
use Nexus\JournalEntry\Exceptions\InvalidJournalEntryNumberException;
use PHPUnit\Framework\TestCase;

final class JournalEntryNumberTest extends TestCase
{
    public function test_fromString_with_prefix_year_sequence_pattern(): void
    {
        $number = JournalEntryNumber::fromString('JE-2024-001234');

        $this->assertSame('JE-2024-001234', $number->value);
        $this->assertSame('JE', $number->prefix);
        $this->assertSame(2024, $number->year);
        $this->assertSame(1234, $number->sequence);
    }

    public function test_fromString_with_prefix_sequence_pattern(): void
    {
        $number = JournalEntryNumber::fromString('GJ001234');

        $this->assertSame('GJ001234', $number->value);
        $this->assertSame('GJ', $number->prefix);
        $this->assertNull($number->year);
        $this->assertSame(1234, $number->sequence);
    }

    public function test_fromString_with_sequence_only_pattern(): void
    {
        $number = JournalEntryNumber::fromString('001234');

        $this->assertSame('001234', $number->value);
        $this->assertNull($number->prefix);
        $this->assertNull($number->year);
        $this->assertSame(1234, $number->sequence);
    }

    public function test_fromString_accepts_any_non_empty_string(): void
    {
        $number = JournalEntryNumber::fromString('CUSTOM-FORMAT-123');

        $this->assertSame('CUSTOM-FORMAT-123', $number->value);
    }

    public function test_fromString_throws_exception_for_empty_string(): void
    {
        $this->expectException(InvalidJournalEntryNumberException::class);
        JournalEntryNumber::fromString('');
    }

    public function test_fromString_throws_exception_for_whitespace_only(): void
    {
        $this->expectException(InvalidJournalEntryNumberException::class);
        JournalEntryNumber::fromString('   ');
    }

    public function test_generate_with_prefix_year_and_sequence(): void
    {
        $number = JournalEntryNumber::generate('JE', 123, 2024);

        $this->assertSame('JE-2024-000123', $number->value);
        $this->assertSame('JE', $number->prefix);
        $this->assertSame(2024, $number->year);
        $this->assertSame(123, $number->sequence);
    }

    public function test_generate_with_prefix_and_sequence_only(): void
    {
        $number = JournalEntryNumber::generate('GJ', 456);

        $this->assertSame('GJ-000456', $number->value);
        $this->assertSame('GJ', $number->prefix);
        $this->assertNull($number->year);
        $this->assertSame(456, $number->sequence);
    }

    public function test_generate_with_custom_separator(): void
    {
        $number = JournalEntryNumber::generate('JE', 789, 2024, '/');

        $this->assertSame('JE/2024/000789', $number->value);
        $this->assertSame('JE', $number->prefix);
        $this->assertSame(2024, $number->year);
        $this->assertSame(789, $number->sequence);
    }

    public function test_generate_pads_sequence_to_six_digits(): void
    {
        $number = JournalEntryNumber::generate('JE', 1, 2024);

        $this->assertSame('JE-2024-000001', $number->value);
    }

    public function test_equals_returns_true_for_same_value(): void
    {
        $number1 = JournalEntryNumber::fromString('JE-2024-001234');
        $number2 = JournalEntryNumber::fromString('JE-2024-001234');

        $this->assertTrue($number1->equals($number2));
    }

    public function test_equals_returns_false_for_different_values(): void
    {
        $number1 = JournalEntryNumber::fromString('JE-2024-001234');
        $number2 = JournalEntryNumber::fromString('JE-2024-001235');

        $this->assertFalse($number1->equals($number2));
    }

    public function test_constructor_throws_exception_for_empty_value(): void
    {
        $this->expectException(InvalidJournalEntryNumberException::class);
        new JournalEntryNumber('');
    }
}
