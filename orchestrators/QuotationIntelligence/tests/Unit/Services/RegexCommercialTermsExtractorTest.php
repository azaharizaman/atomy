<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\Services;

use Nexus\QuotationIntelligence\Services\RegexCommercialTermsExtractor;
use PHPUnit\Framework\TestCase;

final class RegexCommercialTermsExtractorTest extends TestCase
{
    public function test_extracts_incoterm_payment_leadtime_and_warranty(): void
    {
        $extractor = new RegexCommercialTermsExtractor();

        $terms = $extractor->extract('Delivery EXW. NET-60. Lead time: 45 days. Warranty: 2 years.');

        $this->assertSame('EXW', $terms['incoterm']);
        $this->assertSame(60, $terms['payment_days']);
        $this->assertSame(45, $terms['lead_time_days']);
        $this->assertSame(24, $terms['warranty_months']);
    }

    public function test_returns_nulls_when_terms_are_absent(): void
    {
        $extractor = new RegexCommercialTermsExtractor();

        $terms = $extractor->extract('Laptop line item without contractual terms');

        $this->assertNull($terms['incoterm']);
        $this->assertNull($terms['payment_days']);
        $this->assertNull($terms['lead_time_days']);
        $this->assertNull($terms['warranty_months']);
    }

    public function test_extracts_alternative_patterns(): void
    {
        $extractor = new RegexCommercialTermsExtractor();

        $terms = $extractor->extract('Terms: Ex-Works. Payment 45 days. Warranty: 6 months.');

        $this->assertSame('EXW', $terms['incoterm']);
        $this->assertSame(45, $terms['payment_days']);
        $this->assertNull($terms['lead_time_days']);
        $this->assertSame(6, $terms['warranty_months']);
    }
}
