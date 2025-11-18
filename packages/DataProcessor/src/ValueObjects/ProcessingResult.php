<?php

declare(strict_types=1);

namespace Nexus\DataProcessor\ValueObjects;

/**
 * Processing Result Value Object
 * 
 * Immutable result from document processing operations.
 */
final readonly class ProcessingResult
{
    /**
     * @param array<string, mixed> $extractedData Key-value pairs of extracted data
     * @param float $confidence Overall confidence score (0-100)
     * @param array<string, float> $fieldConfidences Per-field confidence scores
     * @param array<string> $warnings Processing warnings
     */
    public function __construct(
        private array $extractedData,
        private float $confidence,
        private array $fieldConfidences = [],
        private array $warnings = []
    ) {
        if ($this->confidence < 0 || $this->confidence > 100) {
            throw new \InvalidArgumentException('Confidence must be between 0 and 100');
        }
    }

    /**
     * Get all extracted data
     * 
     * @return array<string, mixed>
     */
    public function getExtractedData(): array
    {
        return $this->extractedData;
    }

    /**
     * Get a specific extracted field value
     */
    public function getField(string $fieldName): mixed
    {
        return $this->extractedData[$fieldName] ?? null;
    }

    /**
     * Get overall confidence score (0-100)
     */
    public function getConfidence(): float
    {
        return $this->confidence;
    }

    /**
     * Get confidence score for a specific field
     */
    public function getFieldConfidence(string $fieldName): ?float
    {
        return $this->fieldConfidences[$fieldName] ?? null;
    }

    /**
     * Get all field confidence scores
     * 
     * @return array<string, float>
     */
    public function getFieldConfidences(): array
    {
        return $this->fieldConfidences;
    }

    /**
     * Get processing warnings
     * 
     * @return array<string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Check if confidence is above threshold
     */
    public function isConfidenceAbove(float $threshold): bool
    {
        return $this->confidence >= $threshold;
    }

    /**
     * Check if result has warnings
     */
    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }

    /**
     * Check if a specific field exists
     */
    public function hasField(string $fieldName): bool
    {
        return array_key_exists($fieldName, $this->extractedData);
    }
}
