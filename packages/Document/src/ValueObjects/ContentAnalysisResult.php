<?php

declare(strict_types=1);

namespace Nexus\Document\ValueObjects;

/**
 * Content analysis result value object.
 *
 * Encapsulates the results of ML-driven document analysis including
 * predicted type, confidence score, extracted metadata, PII detection,
 * and suggested tags.
 * Immutable by design.
 */
final readonly class ContentAnalysisResult
{
    /**
     * @param DocumentType|null $predictedType Predicted document type (null if no prediction)
     * @param float $confidenceScore Confidence score (0.0 to 1.0)
     * @param array<string, mixed> $extractedMetadata Extracted key-value metadata
     * @param bool $containsPII Whether PII was detected
     * @param array<string> $suggestedTags Auto-generated tags based on content
     * @param array<string, mixed> $rawAnalysis Raw ML model output for debugging
     */
    public function __construct(
        public ?DocumentType $predictedType,
        public float $confidenceScore,
        public array $extractedMetadata = [],
        public bool $containsPII = false,
        public array $suggestedTags = [],
        public array $rawAnalysis = []
    ) {
        if ($confidenceScore < 0.0 || $confidenceScore > 1.0) {
            throw new \InvalidArgumentException('Confidence score must be between 0.0 and 1.0');
        }
    }

    /**
     * Create from an array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            predictedType: isset($data['predicted_type'])
                ? DocumentType::from($data['predicted_type'])
                : null,
            confidenceScore: (float) ($data['confidence_score'] ?? 0.0),
            extractedMetadata: $data['extracted_metadata'] ?? [],
            containsPII: (bool) ($data['contains_pii'] ?? false),
            suggestedTags: $data['suggested_tags'] ?? [],
            rawAnalysis: $data['raw_analysis'] ?? []
        );
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'predicted_type' => $this->predictedType?->value,
            'confidence_score' => $this->confidenceScore,
            'extracted_metadata' => $this->extractedMetadata,
            'contains_pii' => $this->containsPII,
            'suggested_tags' => $this->suggestedTags,
            'raw_analysis' => $this->rawAnalysis,
        ];
    }

    /**
     * Check if the prediction is reliable (confidence >= threshold).
     */
    public function isReliable(float $threshold = 0.8): bool
    {
        return $this->confidenceScore >= $threshold;
    }

    /**
     * Check if any prediction was made.
     */
    public function hasPrediction(): bool
    {
        return $this->predictedType !== null;
    }

    /**
     * Get an extracted metadata field.
     */
    public function getExtractedField(string $key, mixed $default = null): mixed
    {
        return $this->extractedMetadata[$key] ?? $default;
    }

    /**
     * Create a null result (no analysis performed).
     */
    public static function null(): self
    {
        return new self(
            predictedType: null,
            confidenceScore: 0.0,
            extractedMetadata: [],
            containsPII: false,
            suggestedTags: [],
            rawAnalysis: []
        );
    }
}
