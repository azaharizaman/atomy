<?php

declare(strict_types=1);

namespace App\Services\QuoteIntake;

interface DecisionTrailRecorderInterface
{
    /**
     * @param array<string, mixed> $summary
     */
    public function recordVendorRecommendationGenerated(string $tenantId, string $rfqId, array $summary): void;

    /**
     * @param array<string, mixed> $summary
     */
    public function recordBuyerShortlistReplaced(string $tenantId, string $rfqId, array $summary): void;

    /**
     * @param array<string, mixed> $summary
     */
    public function recordManualSourceLineEvent(
        string $tenantId,
        string $rfqId,
        string $quoteSubmissionId,
        string $sourceLineId,
        string $eventType,
        array $summary = [],
    ): void;
}
