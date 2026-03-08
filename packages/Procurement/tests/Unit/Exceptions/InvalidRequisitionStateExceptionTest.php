<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Exceptions;

use Nexus\Procurement\Exceptions\InvalidRequisitionStateException;
use PHPUnit\Framework\TestCase;

final class InvalidRequisitionStateExceptionTest extends TestCase
{
    public function test_cannot_approve_status_returns_correct_message(): void
    {
        $e = InvalidRequisitionStateException::cannotApproveStatus('req-1', 'approved');

        self::assertStringContainsString('req-1', $e->getMessage());
        self::assertStringContainsString('approved', $e->getMessage());
    }

    public function test_cannot_convert_status_returns_correct_message(): void
    {
        $e = InvalidRequisitionStateException::cannotConvertStatus('req-1', 'draft');

        self::assertStringContainsString('req-1', $e->getMessage());
        self::assertStringContainsString('draft', $e->getMessage());
    }

    public function test_already_converted_returns_correct_message(): void
    {
        $e = InvalidRequisitionStateException::alreadyConverted('req-1');

        self::assertStringContainsString('req-1', $e->getMessage());
        self::assertStringContainsString('already been converted', $e->getMessage());
    }
}
