<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\ValueObjects;

/**
 * Immutable value object linking a field to its evidence snippet.
 * 
 * Used for GAAP/IFRS audit traceability.
 */
final readonly class QuoteSnippet
{
    /**
     * @param string $fieldName Name of the normalized field (e.g., 'unit_price', 'lead_time')
     * @param ExtractionEvidence $evidence The source coordinates and raw text
     * @param string|null $context Additional contextual snippet (e.g., the surrounding sentence)
     */
    public function __construct(
        public string $fieldName,
        public ExtractionEvidence $evidence,
        public ?string $context = null
    ) {
    }

    /**
     * Convert to array for serialization.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'field_name' => $this->fieldName,
            'evidence' => $this->evidence->toArray(),
            'context' => $this->context,
        ];
    }
}
