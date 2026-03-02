<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\DTOs;

use Nexus\QuotationIntelligence\ValueObjects\QuoteSnippet;

/**
 * Data Transfer Object for a normalized quote line item.
 * 
 * Stores both machine-predicted (AI) values and final verified values
 * with full traceability snippets.
 */
final readonly class NormalizedQuoteLine
{
    /**
     * @param string $rfqLineId Reference to the original RFQ line item
     * @param string $vendorDescription The literal description from the vendor's PDF
     * @param string $taxonomyCode The mapped UNSPSC/eCl@ss code
     * @param float $quotedQuantity Original quantity
     * @param string $quotedUnit Original Unit of Measure
     * @param float $normalizedQuantity Converted quantity in RFQ base unit
     * @param float $quotedUnitPrice Original unit price
     * @param float $normalizedUnitPrice Converted unit price in RFQ base unit/currency
     * @param float $aiConfidence Confidence score for extraction/classification (0-1)
     * @param array<QuoteSnippet> $snippets Evidence snippets for fields
     * @param array<string, mixed> $metadata Additional extracted attributes
     */
    public function __construct(
        public string $rfqLineId,
        public string $vendorDescription,
        public string $taxonomyCode,
        public float $quotedQuantity,
        public string $quotedUnit,
        public float $normalizedQuantity,
        public float $quotedUnitPrice,
        public float $normalizedUnitPrice,
        public float $aiConfidence,
        public array $snippets = [],
        public array $metadata = []
    ) {
    }

    /**
     * Check if this line is an outlier (variance check logic elsewhere).
     */
    public function hasLowConfidence(float $threshold = 0.9): bool
    {
        return $this->aiConfidence < $threshold;
    }

    /**
     * Get snippet for a specific field.
     */
    public function getSnippet(string $fieldName): ?QuoteSnippet
    {
        foreach ($this->snippets as $snippet) {
            if ($snippet->fieldName === $fieldName) {
                return $snippet;
            }
        }

        return null;
    }

    /**
     * Convert to array for serialization.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rfq_line_id' => $this->rfqLineId,
            'vendor_description' => $this->vendorDescription,
            'taxonomy_code' => $this->taxonomyCode,
            'quoted_quantity' => $this->quotedQuantity,
            'quoted_unit' => $this->quotedUnit,
            'normalized_quantity' => $this->normalizedQuantity,
            'quoted_unit_price' => $this->quotedUnitPrice,
            'normalized_unit_price' => $this->normalizedUnitPrice,
            'ai_confidence' => $this->aiConfidence,
            'snippets' => array_map(fn(QuoteSnippet $s) => $s->toArray(), $this->snippets),
            'metadata' => $this->metadata,
        ];
    }
}
