<?php

declare(strict_types=1);

namespace Nexus\Vendor\Tests\Unit\ValueObjects;

use Nexus\Vendor\ValueObjects\VendorId;
use PHPUnit\Framework\TestCase;

final class VendorIdTest extends TestCase
{
    public function testItRejectsEmptyInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vendor ID cannot be empty.');

        new VendorId('   ');
    }

    public function testItTrimsAndNormalizesToUppercase(): void
    {
        $vendorId = new VendorId('  01j8z8v7k2m9c4a5b6d7e8f9g0  ');

        self::assertSame('01J8Z8V7K2M9C4A5B6D7E8F9G0', $vendorId->getValue());
        self::assertSame('01J8Z8V7K2M9C4A5B6D7E8F9G0', (string) $vendorId);
    }
}
