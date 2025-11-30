<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\Backoffice\ValueObjects\TransferType;

/**
 * Unit tests for TransferType enum.
 */
class TransferTypeTest extends TestCase
{
    public function test_promotion_type_value(): void
    {
        $this->assertSame('promotion', TransferType::PROMOTION->value);
    }

    public function test_lateral_move_type_value(): void
    {
        $this->assertSame('lateral_move', TransferType::LATERAL_MOVE->value);
    }

    public function test_demotion_type_value(): void
    {
        $this->assertSame('demotion', TransferType::DEMOTION->value);
    }

    public function test_relocation_type_value(): void
    {
        $this->assertSame('relocation', TransferType::RELOCATION->value);
    }

    public function test_from_valid_value(): void
    {
        $this->assertSame(TransferType::PROMOTION, TransferType::from('promotion'));
        $this->assertSame(TransferType::LATERAL_MOVE, TransferType::from('lateral_move'));
        $this->assertSame(TransferType::DEMOTION, TransferType::from('demotion'));
        $this->assertSame(TransferType::RELOCATION, TransferType::from('relocation'));
    }

    public function test_from_invalid_value_throws_exception(): void
    {
        $this->expectException(\ValueError::class);
        TransferType::from('invalid');
    }

    public function test_all_cases_are_defined(): void
    {
        $cases = TransferType::cases();
        $this->assertCount(4, $cases);
        $this->assertContains(TransferType::PROMOTION, $cases);
        $this->assertContains(TransferType::LATERAL_MOVE, $cases);
        $this->assertContains(TransferType::DEMOTION, $cases);
        $this->assertContains(TransferType::RELOCATION, $cases);
    }
}
