<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Exceptions;

use Nexus\QuotationIntelligence\Exceptions\MissingRfqContextException;
use Nexus\QuotationIntelligence\Exceptions\QuotationIntelligenceException;
use PHPUnit\Framework\TestCase;

final class MissingRfqContextExceptionTest extends TestCase
{
    public function test_extends_base_exception(): void
    {
        $exception = new MissingRfqContextException('missing rfq');
        $this->assertInstanceOf(QuotationIntelligenceException::class, $exception);
    }
}

