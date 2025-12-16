<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\DuplicateMatchType;

/**
 * Represents a potential duplicate invoice match.
 *
 * Contains details about the matching invoice and the type of match detected.
 */
final readonly class DuplicateMatch
{
    /**
     * @param string $matchedInvoiceId ID of the potentially duplicate invoice
     * @param string $matchedInvoiceNumber Invoice number of the matched invoice
     * @param DuplicateMatchType $matchType Type of duplicate match
     * @param float $confidenceScore Match confidence (0.0 to 1.0)
     * @param Money $matchedAmount Amount of the matched invoice
     * @param \DateTimeImmutable $matchedDate Date of the matched invoice
     * @param string $matchedStatus Status of the matched invoice
     * @param array<string, mixed> $matchDetails Additional match details
     */
    public function __construct(
        public string $matchedInvoiceId,
        public string $matchedInvoiceNumber,
        public DuplicateMatchType $matchType,
        public float $confidenceScore,
        public Money $matchedAmount,
        public \DateTimeImmutable $matchedDate,
        public string $matchedStatus,
        public array $matchDetails = [],
    ) {}

    /**
     * Check if this match should block invoice processing.
     */
    public function shouldBlock(): bool
    {
        return $this->matchType->shouldBlockProcessing()
            || $this->confidenceScore >= 0.95;
    }

    /**
     * Get the risk level of this match.
     */
    public function getRiskLevel(): string
    {
        return $this->matchType->getRiskLevel();
    }

    /**
     * Get human-readable description of the match.
     */
    public function getDescription(): string
    {
        return sprintf(
            '%s (Invoice #%s, %s %s, dated %s, status: %s)',
            $this->matchType->getDescription(),
            $this->matchedInvoiceNumber,
            $this->matchedAmount->getCurrency(),
            number_format($this->matchedAmount->getAmount(), 2),
            $this->matchedDate->format('Y-m-d'),
            $this->matchedStatus
        );
    }

    /**
     * Get recommended action for this match.
     */
    public function getRecommendedAction(): string
    {
        return $this->matchType->getRecommendedAction();
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'matched_invoice_id' => $this->matchedInvoiceId,
            'matched_invoice_number' => $this->matchedInvoiceNumber,
            'match_type' => $this->matchType->value,
            'confidence_score' => $this->confidenceScore,
            'matched_amount' => $this->matchedAmount->getAmount(),
            'matched_currency' => $this->matchedAmount->getCurrency(),
            'matched_date' => $this->matchedDate->format('Y-m-d'),
            'matched_status' => $this->matchedStatus,
            'risk_level' => $this->getRiskLevel(),
            'should_block' => $this->shouldBlock(),
            'description' => $this->getDescription(),
            'recommended_action' => $this->getRecommendedAction(),
            'match_details' => $this->matchDetails,
        ];
    }
}
