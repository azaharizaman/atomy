<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Exceptions;

use Nexus\QuotationIntelligence\Exceptions\QuotationIntelligenceException;
use PHPUnit\Framework\TestCase;

final class QuotationIntelligenceExceptionTest extends TestCase
{
    public function test_is_runtime_exception(): void
    {
        $exception = new QuotationIntelligenceException('base');
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}

