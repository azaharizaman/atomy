<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\ValueObjects;

use Nexus\QuotationIntelligence\ValueObjects\ExtractionEvidence;
use Nexus\QuotationIntelligence\ValueObjects\QuoteSnippet;
use PHPUnit\Framework\TestCase;

final class QuoteSnippetTest extends TestCase
{
    public function test_to_array(): void
    {
        $evidence = new ExtractionEvidence(
            documentId: 'doc-1',
            page: 1,
            bbox: ['x' => 1.0, 'y' => 1.0, 'w' => 1.0, 'h' => 1.0],
            rawText: 'snippet'
        );

        $snippet = new QuoteSnippet('description', $evidence);
        $asArray = $snippet->toArray();

        $this->assertSame('description', $asArray['field_name']);
        $this->assertSame('doc-1', $asArray['evidence']['document_id']);
    }
}

