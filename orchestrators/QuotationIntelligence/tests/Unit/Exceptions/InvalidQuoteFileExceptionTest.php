<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Exceptions;

use Nexus\QuotationIntelligence\Exceptions\InvalidQuoteFileException;
use Nexus\QuotationIntelligence\Exceptions\QuotationIntelligenceException;
use PHPUnit\Framework\TestCase;

final class InvalidQuoteFileExceptionTest extends TestCase
{
    public function test_extends_base_exception(): void
    {
        $exception = new InvalidQuoteFileException('invalid');
        $this->assertInstanceOf(QuotationIntelligenceException::class, $exception);
    }
}

