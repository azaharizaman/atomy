<?php

declare(strict_types=1);

namespace Nexus\Vendor\Tests\Unit\ValueObjects;

use Nexus\Vendor\ValueObjects\RegistrationNumber;
use PHPUnit\Framework\TestCase;

final class RegistrationNumberTest extends TestCase
{
    public function testItRejectsEmptyInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Registration number cannot be empty.');

        new RegistrationNumber('   ');
    }

    public function testItTrimsInput(): void
    {
        $registrationNumber = new RegistrationNumber("  202401234567  ");

        self::assertSame('202401234567', $registrationNumber->getValue());
        self::assertSame('202401234567', (string) $registrationNumber);
    }
}
