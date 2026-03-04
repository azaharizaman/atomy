<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Exceptions;

use Nexus\QuotationIntelligence\Exceptions\InvalidNormalizationContextException;
use Nexus\QuotationIntelligence\Exceptions\QuotationIntelligenceException;
use PHPUnit\Framework\TestCase;

final class InvalidNormalizationContextExceptionTest extends TestCase
{
    public function test_extends_base_exception(): void
    {
        $exception = new InvalidNormalizationContextException('invalid context');
        $this->assertInstanceOf(QuotationIntelligenceException::class, $exception);
    }
}

