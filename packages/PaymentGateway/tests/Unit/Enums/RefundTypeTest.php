<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Tests\Unit\Enums;

use Nexus\PaymentGateway\Enums\RefundType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RefundType::class)]
final class RefundTypeTest extends TestCase
{
    #[Test]
    public function it_has_all_expected_types(): void
    {
        $expectedTypes = ['FULL', 'PARTIAL'];

        $actualTypes = array_map(
            fn (RefundType $type) => $type->name,
            RefundType::cases()
        );

        $this->assertSame($expectedTypes, $actualTypes);
    }

    #[Test]
    public function it_returns_correct_labels(): void
    {
        $this->assertSame('Full Refund', RefundType::FULL->label());
        $this->assertSame('Partial Refund', RefundType::PARTIAL->label());
    }

    #[Test]
    public function it_has_correct_backing_values(): void
    {
        $this->assertSame('full', RefundType::FULL->value);
        $this->assertSame('partial', RefundType::PARTIAL->value);
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $this->assertSame(RefundType::FULL, RefundType::from('full'));
        $this->assertSame(RefundType::PARTIAL, RefundType::from('partial'));
    }
}
