<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Exceptions;

use Nexus\Procurement\Exceptions\InvalidPurchaseOrderDataException;
use PHPUnit\Framework\TestCase;

final class InvalidPurchaseOrderDataExceptionTest extends TestCase
{
    public function test_no_lines_returns_correct_message(): void
    {
        $e = InvalidPurchaseOrderDataException::noLines();

        self::assertStringContainsString('at least one line', $e->getMessage());
    }

    public function test_missing_vendor_returns_correct_message(): void
    {
        $e = InvalidPurchaseOrderDataException::missingVendor();

        self::assertStringContainsString('vendor', $e->getMessage());
    }

    public function test_missing_required_field_returns_correct_message(): void
    {
        $e = InvalidPurchaseOrderDataException::missingRequiredField('number');

        self::assertStringContainsString('number', $e->getMessage());
    }
}
