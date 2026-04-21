<?php

declare(strict_types=1);

namespace Nexus\Vendor\Tests\Unit\ValueObjects;

use Nexus\Vendor\ValueObjects\RegistrationNumber;
use PHPUnit\Framework\TestCase;

final class RegistrationNumberTest extends TestCase
{
    public function test_it_rejects_empty_input(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Registration number cannot be empty.');

        new RegistrationNumber('   ');
    }

    public function test_it_trims_input(): void
    {
        $registrationNumber = new RegistrationNumber("  202401234567  ");

        self::assertSame('202401234567', $registrationNumber->getValue());
        self::assertSame('202401234567', (string) $registrationNumber);
    }
}
