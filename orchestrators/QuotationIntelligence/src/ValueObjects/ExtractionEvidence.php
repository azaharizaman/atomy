<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\ValueObjects;

/**
 * Immutable value object representing the source evidence of an extraction.
 * 
 * Captures the precise location within a document where a value was found.
 */
final readonly class ExtractionEvidence
{
    /**
     * @param string $documentId Reference to the source Nexus\Document
     * @param int $page 1-based page number
     * @param array{x: float, y: float, w: float, h: float} $bbox Bounding box in percentage (0-100)
     * @param string $rawText The original unnormalized text extracted from this location
     */
    public function __construct(
        public string $documentId,
        public int $page,
        public array $bbox,
        public string $rawText
    ) {
        if ($page < 1) {
            throw new \InvalidArgumentException('Page number must be at least 1');
        }
    }

    /**
     * Convert to array for serialization.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'document_id' => $this->documentId,
            'page' => $this->page,
            'bbox' => $this->bbox,
            'raw_text' => $this->rawText,
        ];
    }
}
