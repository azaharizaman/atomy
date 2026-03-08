<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Exceptions;

use Nexus\Procurement\Exceptions\InvalidGoodsReceiptDataException;
use PHPUnit\Framework\TestCase;

final class InvalidGoodsReceiptDataExceptionTest extends TestCase
{
    public function test_no_lines_returns_correct_message(): void
    {
        $e = InvalidGoodsReceiptDataException::noLines();

        self::assertStringContainsString('at least one line', $e->getMessage());
    }

    public function test_quantity_exceeds_po_returns_correct_message(): void
    {
        $e = InvalidGoodsReceiptDataException::quantityExceedsPo('PO-001-L001', 150.0, 100.0);

        self::assertStringContainsString('150', $e->getMessage());
        self::assertStringContainsString('100', $e->getMessage());
    }

    public function test_missing_po_line_reference_returns_correct_message(): void
    {
        $e = InvalidGoodsReceiptDataException::missingPoLineReference(1);

        self::assertStringContainsString('1', $e->getMessage());
    }

    public function test_missing_required_field_returns_correct_message(): void
    {
        $e = InvalidGoodsReceiptDataException::missingRequiredField('received_date');

        self::assertStringContainsString('received_date', $e->getMessage());
    }
}
