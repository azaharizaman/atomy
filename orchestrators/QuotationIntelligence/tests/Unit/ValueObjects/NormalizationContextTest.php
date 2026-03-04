<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\ValueObjects;

use Nexus\QuotationIntelligence\ValueObjects\NormalizationContext;
use PHPUnit\Framework\TestCase;

final class NormalizationContextTest extends TestCase
{
    public function test_to_array_with_lock_date(): void
    {
        $context = new NormalizationContext(
            baseUnit: 'EA',
            baseCurrency: 'USD',
            fxLockDate: new \DateTimeImmutable('2026-03-01')
        );

        $asArray = $context->toArray();
        $this->assertSame('EA', $asArray['base_unit']);
        $this->assertSame('USD', $asArray['base_currency']);
        $this->assertSame('2026-03-01', $asArray['fx_lock_date']);
    }

    public function test_to_array_without_lock_date(): void
    {
        $context = new NormalizationContext('EA', 'USD', null);
        $this->assertNull($context->toArray()['fx_lock_date']);
    }
}

