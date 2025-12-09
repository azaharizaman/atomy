<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Tests\Unit\Enums;

use Nexus\ProcurementOperations\Enums\MemoType;
use PHPUnit\Framework\TestCase;

final class MemoTypeTest extends TestCase
{
    public function test_credit_memo_has_negative_gl_multiplier(): void
    {
        $type = MemoType::CREDIT;

        $this->assertSame(-1, $type->glMultiplier());
        $this->assertTrue($type->reducesVendorBalance());
    }

    public function test_debit_memo_has_positive_gl_multiplier(): void
    {
        $type = MemoType::DEBIT;

        $this->assertSame(1, $type->glMultiplier());
        $this->assertFalse($type->reducesVendorBalance());
    }

    public function test_credit_memo_label(): void
    {
        $this->assertSame('Credit Memo', MemoType::CREDIT->getLabel());
    }

    public function test_debit_memo_label(): void
    {
        $this->assertSame('Debit Memo', MemoType::DEBIT->getLabel());
    }

    public function test_all_cases_have_labels(): void
    {
        foreach (MemoType::cases() as $type) {
            $this->assertNotEmpty($type->getLabel());
        }
    }

    public function test_all_cases_have_gl_multipliers(): void
    {
        foreach (MemoType::cases() as $type) {
            $multiplier = $type->glMultiplier();
            $this->assertContains($multiplier, [-1, 1]);
        }
    }
}
