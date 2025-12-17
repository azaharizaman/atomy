<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Tests\Unit\Exceptions;

use Nexus\KycVerification\Exceptions\KycVerificationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(KycVerificationException::class)]
final class KycVerificationExceptionTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_message(): void
    {
        $exception = new KycVerificationException('Test error message');

        $this->assertSame('Test error message', $exception->getMessage());
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    #[Test]
    public function it_can_be_created_with_code(): void
    {
        $exception = new KycVerificationException('Error', 500);

        $this->assertSame(500, $exception->getCode());
    }

    #[Test]
    public function it_can_be_created_with_previous_exception(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new KycVerificationException('Wrapper error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
