<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Exceptions;

use Nexus\QuotationIntelligence\Exceptions\QuotationIntelligenceException;
use Nexus\QuotationIntelligence\Exceptions\UomNormalizationException;
use PHPUnit\Framework\TestCase;

final class UomNormalizationExceptionTest extends TestCase
{
    public function test_extends_base_exception(): void
    {
        $exception = new UomNormalizationException('uom');
        $this->assertInstanceOf(QuotationIntelligenceException::class, $exception);
    }
}

