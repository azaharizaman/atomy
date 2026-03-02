<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Services;

use Nexus\QuotationIntelligence\Contracts\RiskAssessmentServiceInterface;
use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;
use Psr\Log\LoggerInterface;

/**
 * Rule-based risk and anomaly assessment service.
 * 
 * Implements basic heuristics for detecting predatory pricing and terms deviation.
 */
final readonly class RuleBasedRiskAssessmentService implements RiskAssessmentServiceInterface
{
    private const PRICE_VARIANCE_THRESHOLD = 0.50; // 50%

    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function assess(string $tenantId, string $rfqId, array $lines): array
    {
        $risks = [];

        $this->logger->info('Running risk assessment for quote lines', [
            'tenant_id' => $tenantId,
            'rfq_id' => $rfqId,
            'line_count' => count($lines),
        ]);

        foreach ($lines as $index => $line) {
            // 1. Check for pricing anomalies (Placeholder for peer comparison in this basic version)
            // In a real coordinator, we'd pass $peerLines from other quotes.
            // For now, we assess in isolation or flag very low confidence AI extractions.
            if ($line->aiConfidence < 0.7) {
                $risks[] = [
                    'level' => 'medium',
                    'message' => "Low AI extraction confidence ({$line->aiConfidence})",
                    'line_index' => $index,
                ];
            }

            // 2. Term deviation check (e.g., searching vendor description for Incoterms)
            $termsRisk = $this->checkTermsDeviation($line->vendorDescription);
            if ($termsRisk !== null) {
                $risks[] = [
                    'level' => 'high',
                    'message' => $termsRisk,
                    'line_index' => $index,
                ];
            }
        }

        return $risks;
    }

    /**
     * @inheritDoc
     */
    public function isPricingAnomaly(NormalizedQuoteLine $line, array $peerLines): bool
    {
        if (empty($peerLines)) {
            return false;
        }

        // Calculate average of peer normalized prices
        $prices = array_map(fn(NormalizedQuoteLine $l) => $l->normalizedUnitPrice, $peerLines);
        $avgPrice = array_sum($prices) / count($prices);

        if ($avgPrice <= 0) {
            return false;
        }

        $variance = abs($line->normalizedUnitPrice - $avgPrice) / $avgPrice;

        return $variance > self::PRICE_VARIANCE_THRESHOLD;
    }

    /**
     * Internal check for common commercial terms in product strings.
     */
    private function checkTermsDeviation(string $text): ?string
    {
        $text = strtoupper($text);
        
        // Example: Flagging EXW (Ex Works) which usually shifts shipping risk to buyer
        if (str_contains($text, 'EXW') || str_contains($text, 'EX-WORKS')) {
            return 'Detected EXW term: shipping risk/cost may be excluded.';
        }

        if (str_contains($text, 'NET-30') || str_contains($text, 'NET 30')) {
            return 'Payment terms NET-30 detected in line item.';
        }

        return null;
    }
}
