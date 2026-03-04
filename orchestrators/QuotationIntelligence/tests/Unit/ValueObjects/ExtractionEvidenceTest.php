<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Tests\Unit\ValueObjects;

use Nexus\QuotationIntelligence\ValueObjects\ExtractionEvidence;
use PHPUnit\Framework\TestCase;

final class ExtractionEvidenceTest extends TestCase
{
    public function test_to_array(): void
    {
        $evidence = new ExtractionEvidence(
            documentId: 'doc-1',
            page: 2,
            bbox: ['x' => 1.0, 'y' => 2.0, 'w' => 3.0, 'h' => 4.0],
            rawText: 'raw text'
        );

        $asArray = $evidence->toArray();

        $this->assertSame('doc-1', $asArray['document_id']);
        $this->assertSame(2, $asArray['page']);
        $this->assertSame('raw text', $asArray['raw_text']);
    }

    public function test_throws_when_page_is_less_than_one(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ExtractionEvidence(
            documentId: 'doc-1',
            page: 0,
            bbox: ['x' => 0.0, 'y' => 0.0, 'w' => 0.0, 'h' => 0.0],
            rawText: 'x'
        );
    }
}

