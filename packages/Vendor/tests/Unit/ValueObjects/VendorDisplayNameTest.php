<?php

declare(strict_types=1);

namespace Nexus\Vendor\Tests\Unit\ValueObjects;

use Nexus\Vendor\ValueObjects\VendorDisplayName;
use PHPUnit\Framework\TestCase;

final class VendorDisplayNameTest extends TestCase
{
    public function testItRejectsEmptyInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vendor display name cannot be empty.');

        new VendorDisplayName('   ');
    }

    public function testItTrimsInput(): void
    {
        $displayName = new VendorDisplayName("  Alpha Supplies  ");

        self::assertSame('Alpha Supplies', $displayName->getValue());
        self::assertSame('Alpha Supplies', (string) $displayName);
    }
}
