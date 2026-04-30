<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

final readonly class RiskInsightFactsDto
{
    /**
     * @param array<int, array<string, mixed>> $riskItems
     * @param array<string, mixed> $manualReview
     */
    public function __construct(
        public string $rfqId,
        public array $riskItems,
        public array $manualReview = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $manualReview = $this->manualReview;
        unset($manualReview["pending_items"]);

        return [
            "rfq_id" => $this->rfqId,
            // 'items' kept for backward compatibility with initial alpha UI
            "items" => $this->riskItems,
            "risk_items" => $this->riskItems,
            "manual_review" => [
                ...$manualReview,
                "pending_items" => count($this->riskItems),
            ],
        ];
    }
}
