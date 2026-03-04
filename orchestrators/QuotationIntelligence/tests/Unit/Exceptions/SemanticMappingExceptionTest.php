<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Exceptions;

use Nexus\QuotationIntelligence\Exceptions\QuotationIntelligenceException;
use Nexus\QuotationIntelligence\Exceptions\SemanticMappingException;
use PHPUnit\Framework\TestCase;

final class SemanticMappingExceptionTest extends TestCase
{
    public function test_extends_base_exception(): void
    {
        $exception = new SemanticMappingException('mapping');
        $this->assertInstanceOf(QuotationIntelligenceException::class, $exception);
    }
}

