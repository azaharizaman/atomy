<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\DTOs;

use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;
use Nexus\QuotationIntelligence\ValueObjects\ExtractionEvidence;
use Nexus\QuotationIntelligence\ValueObjects\QuoteSnippet;
use PHPUnit\Framework\TestCase;

final class NormalizedQuoteLineTest extends TestCase
{
    public function test_get_snippet_and_to_array(): void
    {
        $evidence = new ExtractionEvidence(
            documentId: 'doc-1',
            page: 1,
            bbox: ['x' => 1.0, 'y' => 2.0, 'w' => 3.0, 'h' => 4.0],
            rawText: 'Laptop'
        );
        $snippet = new QuoteSnippet('description', $evidence);

        $line = new NormalizedQuoteLine(
            rfqLineId: 'line-1',
            vendorDescription: 'Laptop',
            taxonomyCode: '43211503',
            quotedQuantity: 1.0,
            quotedUnit: 'EA',
            normalizedQuantity: 1.0,
            quotedUnitPrice: 1000.0,
            normalizedUnitPrice: 1000.0,
            aiConfidence: 0.8,
            snippets: [$snippet]
        );

        $this->assertTrue($line->hasLowConfidence());
        $this->assertFalse($line->hasLowConfidence(0.7));
        $this->assertSame($snippet, $line->getSnippet('description'));
        $this->assertNull($line->getSnippet('missing_field'));

        $asArray = $line->toArray();
        $this->assertSame('line-1', $asArray['rfq_line_id']);
        $this->assertSame('43211503', $asArray['taxonomy_code']);
        $this->assertCount(1, $asArray['snippets']);
    }
}

