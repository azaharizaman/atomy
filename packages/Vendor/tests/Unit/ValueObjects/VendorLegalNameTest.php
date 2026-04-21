<?php

declare(strict_types=1);

namespace Nexus\Vendor\Tests\Unit\ValueObjects;

use Nexus\Vendor\ValueObjects\VendorLegalName;
use PHPUnit\Framework\TestCase;

final class VendorLegalNameTest extends TestCase
{
    public function test_it_rejects_empty_input(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vendor legal name cannot be empty.');

        new VendorLegalName('   ');
    }

    public function test_it_trims_input(): void
    {
        $legalName = new VendorLegalName("  Alpha Supplies Sdn Bhd  ");

        self::assertSame('Alpha Supplies Sdn Bhd', $legalName->getValue());
        self::assertSame('Alpha Supplies Sdn Bhd', (string) $legalName);
    }
}
