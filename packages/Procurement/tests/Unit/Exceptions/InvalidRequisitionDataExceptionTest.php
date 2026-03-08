<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Exceptions;

use Nexus\Procurement\Exceptions\InvalidRequisitionDataException;
use PHPUnit\Framework\TestCase;

final class InvalidRequisitionDataExceptionTest extends TestCase
{
    public function test_no_lines_returns_correct_message(): void
    {
        $e = InvalidRequisitionDataException::noLines();

        self::assertInstanceOf(InvalidRequisitionDataException::class, $e);
        self::assertStringContainsString('at least one line', $e->getMessage());
    }

    public function test_invalid_total_estimate_returns_correct_message(): void
    {
        $e = InvalidRequisitionDataException::invalidTotalEstimate(1000.0, 950.0);

        self::assertStringContainsString('1000', $e->getMessage());
        self::assertStringContainsString('950', $e->getMessage());
    }

    public function test_missing_required_field_returns_correct_message(): void
    {
        $e = InvalidRequisitionDataException::missingRequiredField('number');

        self::assertStringContainsString('number', $e->getMessage());
    }
}
