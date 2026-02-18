<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\ValueObjects;

use DateTimeImmutable;

/**
 * Value object representing the result of an Available-to-Promise calculation.
 *
 * Encapsulates the promised delivery date, confidence score, and supporting
 * details for customer order promising.
 *
 * @see \Nexus\SupplyChainOperations\Coordinators\DynamicLeadTimeCoordinator
 */
final readonly class AvailableToPromiseResult
{
    /**
     * @param DateTimeImmutable $promisedDate The calculated delivery date
     * @param float $confidence Confidence score (0.0 to 1.0) in the promise
     * @param bool $availableNow Whether the full quantity is available now
     * @param bool $requiresProcurement Whether procurement is needed to fulfill
     * @param int|null $estimatedLeadTimeDays Total lead time in days (if procurement needed)
     * @param float|null $shortageQuantity Quantity that needs to be procured
     * @param array<string, mixed> $metadata Additional context (vendor, risk factors, etc.)
     */
    public function __construct(
        public DateTimeImmutable $promisedDate,
        public float $confidence,
        public bool $availableNow,
        public bool $requiresProcurement,
        public ?int $estimatedLeadTimeDays = null,
        public ?float $shortageQuantity = null,
        public array $metadata = []
    ) {
        // Validate confidence is within bounds
        if ($confidence < 0.0 || $confidence > 1.0) {
            throw new \InvalidArgumentException(
                "Confidence must be between 0.0 and 1.0, got {$confidence}"
            );
        }
    }

    /**
     * Get the promised date formatted as a string.
     */
    public function getPromisedDateString(string $format = 'Y-m-d'): string
    {
        return $this->promisedDate->format($format);
    }

    /**
     * Get confidence as a percentage (0-100).
     */
    public function getConfidencePercentage(): float
    {
        return $this->confidence * 100;
    }

    /**
     * Check if the promise has high confidence (> 80%).
     */
    public function isHighConfidence(): bool
    {
        return $this->confidence >= 0.8;
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'promised_date' => $this->promisedDate->format('c'),
            'confidence' => $this->confidence,
            'confidence_percentage' => $this->getConfidencePercentage(),
            'available_now' => $this->availableNow,
            'requires_procurement' => $this->requiresProcurement,
            'estimated_lead_time_days' => $this->estimatedLeadTimeDays,
            'shortage_quantity' => $this->shortageQuantity,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Create a result for in-stock availability.
     */
    public static function availableNow(DateTimeImmutable $now, float $confidence = 0.95): self
    {
        return new self(
            promisedDate: $now,
            confidence: $confidence,
            availableNow: true,
            requiresProcurement: false
        );
    }

    /**
     * Create a result for unavailable items.
     */
    public static function unavailable(string $reason): self
    {
        return new self(
            promisedDate: new DateTimeImmutable('+365 days'),
            confidence: 0.0,
            availableNow: false,
            requiresProcurement: true,
            metadata: ['unavailable_reason' => $reason]
        );
    }
}
