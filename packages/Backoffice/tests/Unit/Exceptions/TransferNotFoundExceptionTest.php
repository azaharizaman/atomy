<?php

declare(strict_types=1);

namespace Nexus\Backoffice\Tests\Unit\Exceptions;

use Nexus\Backoffice\Exceptions\TransferNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TransferNotFoundException.
 */
class TransferNotFoundExceptionTest extends TestCase
{
    public function test_exception_message_includes_identifier(): void
    {
        $exception = new TransferNotFoundException('transfer-123');
        $this->assertStringContainsString('transfer-123', $exception->getMessage());
    }

    public function test_exception_extends_exception(): void
    {
        $exception = new TransferNotFoundException('transfer-123');
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
