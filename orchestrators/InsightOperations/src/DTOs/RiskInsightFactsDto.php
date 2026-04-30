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
            // TODO(alpha-ui-deprecation): legacy `items` alias is removed; consume `risk_items` only.
            "risk_items" => $this->riskItems,
            "manual_review" => [
                ...$manualReview,
                "pending_items" => count($this->riskItems),
            ],
        ];
    }
}
