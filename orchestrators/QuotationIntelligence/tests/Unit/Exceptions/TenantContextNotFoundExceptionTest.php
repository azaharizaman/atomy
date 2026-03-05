<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Exceptions;

use Nexus\QuotationIntelligence\Exceptions\QuotationIntelligenceException;
use Nexus\QuotationIntelligence\Exceptions\TenantContextNotFoundException;
use PHPUnit\Framework\TestCase;

final class TenantContextNotFoundExceptionTest extends TestCase
{
    public function test_extends_base_exception(): void
    {
        $exception = new TenantContextNotFoundException('tenant');
        $this->assertInstanceOf(QuotationIntelligenceException::class, $exception);
    }
}

