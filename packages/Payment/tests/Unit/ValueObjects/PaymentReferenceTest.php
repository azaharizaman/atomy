<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\ValueObjects;

use Nexus\Payment\Exceptions\InvalidPaymentReferenceException;
use Nexus\Payment\ValueObjects\PaymentReference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PaymentReference::class)]
final class PaymentReferenceTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_value(): void
    {
        $reference = new PaymentReference('REF-001');

        $this->assertSame('REF-001', $reference->value);
        $this->assertNull($reference->type);
        $this->assertNull($reference->prefix);
    }

    #[Test]
    public function it_can_be_created_with_type_and_prefix(): void
    {
        $reference = new PaymentReference('001', 'invoice', 'INV');

        $this->assertSame('001', $reference->value);
        $this->assertSame('invoice', $reference->type);
        $this->assertSame('INV', $reference->prefix);
    }

    #[Test]
    public function it_throws_exception_for_empty_value(): void
    {
        $this->expectException(InvalidPaymentReferenceException::class);
        $this->expectExceptionMessage('cannot be empty');

        new PaymentReference('');
    }

    #[Test]
    public function it_throws_exception_for_whitespace_only_value(): void
    {
        $this->expectException(InvalidPaymentReferenceException::class);
        $this->expectExceptionMessage('cannot be empty');

        new PaymentReference('   ');
    }

    #[Test]
    public function it_throws_exception_for_value_exceeding_100_characters(): void
    {
        $this->expectException(InvalidPaymentReferenceException::class);
        $this->expectExceptionMessage('cannot exceed 100 characters');

        new PaymentReference(str_repeat('x', 101));
    }

    #[Test]
    public function it_accepts_value_with_exactly_100_characters(): void
    {
        $value = str_repeat('x', 100);
        $reference = new PaymentReference($value);

        $this->assertSame($value, $reference->value);
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $reference = PaymentReference::fromString('REF-002');

        $this->assertSame('REF-002', $reference->value);
        $this->assertNull($reference->type);
    }

    #[Test]
    public function it_can_be_created_with_type(): void
    {
        $reference = PaymentReference::withType('ORD-001', 'order');

        $this->assertSame('ORD-001', $reference->value);
        $this->assertSame('order', $reference->type);
    }

    #[Test]
    public function it_can_create_invoice_reference(): void
    {
        $reference = PaymentReference::invoice('2024-00001');

        $this->assertSame('2024-00001', $reference->value);
        $this->assertSame('invoice', $reference->type);
        $this->assertSame('INV', $reference->prefix);
    }

    #[Test]
    public function it_can_create_order_reference(): void
    {
        $reference = PaymentReference::order('2024-00001');

        $this->assertSame('2024-00001', $reference->value);
        $this->assertSame('order', $reference->type);
        $this->assertSame('ORD', $reference->prefix);
    }

    #[Test]
    public function it_displays_value_without_prefix(): void
    {
        $reference = new PaymentReference('REF-001');

        $this->assertSame('REF-001', $reference->display());
    }

    #[Test]
    public function it_displays_value_with_prefix(): void
    {
        $reference = new PaymentReference('001', 'invoice', 'INV');

        $this->assertSame('INV-001', $reference->display());
    }

    #[Test]
    public function it_compares_equal_references(): void
    {
        $reference1 = new PaymentReference('REF-001', 'invoice', 'INV');
        $reference2 = new PaymentReference('REF-001', 'invoice', 'INV');

        $this->assertTrue($reference1->equals($reference2));
    }

    #[Test]
    public function it_compares_different_references(): void
    {
        $reference1 = new PaymentReference('REF-001');
        $reference2 = new PaymentReference('REF-002');

        $this->assertFalse($reference1->equals($reference2));
    }

    #[Test]
    public function it_converts_to_string(): void
    {
        $reference = new PaymentReference('REF-001');

        $this->assertSame('REF-001', (string) $reference);
    }
}
