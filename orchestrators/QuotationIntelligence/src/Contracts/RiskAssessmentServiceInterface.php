<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Contracts;

use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;

/**
 * Interface for risk and anomaly assessment of normalized vendor quotes.
 */
interface RiskAssessmentServiceInterface
{
    /**
     * Analyze normalized quote lines for pricing anomalies and terms deviations.
     * 
     * @param string $tenantId
     * @param string $rfqId Associated RFQ context for comparison
     * @param array<NormalizedQuoteLine> $lines The normalized lines to assess
     * 
     * @return array<string, array{level: string, message: string, line_index?: int}>
     */
    public function assess(string $tenantId, string $rfqId, array $lines): array;

    /**
     * Check for pricing outliers (e.g., predatory pricing > 50% variance).
     * 
     * @param NormalizedQuoteLine $line The line under assessment
     * @param array<NormalizedQuoteLine> $peerLines Lines from other vendor bids
     * 
     * @return bool True if pricing is an outlier
     */
    public function isPricingAnomaly(NormalizedQuoteLine $line, array $peerLines): bool;
}
