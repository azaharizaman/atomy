<?php

declare(strict_types=1);

namespace Nexus\Procurement\Tests\Unit\Exceptions;

use Nexus\Procurement\Exceptions\VendorQuoteNotFoundException;
use PHPUnit\Framework\TestCase;

final class VendorQuoteNotFoundExceptionTest extends TestCase
{
    public function test_for_id_returns_exception_with_message(): void
    {
        $tenantId = 'tenant-1';
        $quoteId = 'quote-123';
        $e = VendorQuoteNotFoundException::forId($tenantId, $quoteId);
        
        self::assertSame("Vendor quote 'quote-123' not found for tenant 'tenant-1'.", $e->getMessage());
    }
}
