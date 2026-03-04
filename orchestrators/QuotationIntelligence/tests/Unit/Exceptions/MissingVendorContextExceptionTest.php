<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Exceptions;

use Nexus\QuotationIntelligence\Exceptions\MissingVendorContextException;
use Nexus\QuotationIntelligence\Exceptions\QuotationIntelligenceException;
use PHPUnit\Framework\TestCase;

final class MissingVendorContextExceptionTest extends TestCase
{
    public function test_extends_base_exception(): void
    {
        $exception = new MissingVendorContextException('missing vendor');
        $this->assertInstanceOf(QuotationIntelligenceException::class, $exception);
    }
}

