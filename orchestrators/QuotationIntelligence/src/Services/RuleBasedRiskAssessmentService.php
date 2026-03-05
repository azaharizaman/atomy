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

            // 2. Commercial terms deviation checks
            $termRisks = $this->checkTermsDeviation($line->metadata['commercial_terms'] ?? []);
            foreach ($termRisks as $termRisk) {
                $risks[] = [
                    'level' => $termRisk['level'],
                    'message' => $termRisk['message'],
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
    /**
     * @param mixed $commercialTerms
     * @return array<int, array{level: string, message: string}>
     */
    private function checkTermsDeviation(mixed $commercialTerms): array
    {
        if (!is_array($commercialTerms)) {
            return [];
        }

        $risks = [];
        $incoterm = strtoupper((string)($commercialTerms['incoterm'] ?? ''));
        $paymentDays = $commercialTerms['payment_days'] ?? null;
        $leadTimeDays = $commercialTerms['lead_time_days'] ?? null;
        $warrantyMonths = $commercialTerms['warranty_months'] ?? null;

        if ($incoterm === 'EXW') {
            $risks[] = [
                'level' => 'high',
                'message' => 'Detected EXW term: shipping risk/cost may be excluded.',
            ];
        }

        if (is_int($paymentDays) && $paymentDays > 45) {
            $risks[] = [
                'level' => 'medium',
                'message' => sprintf('Long payment term detected (NET-%d).', $paymentDays),
            ];
        }

        if (is_int($leadTimeDays) && $leadTimeDays > 30) {
            $risks[] = [
                'level' => 'medium',
                'message' => sprintf('Long lead time detected (%d days).', $leadTimeDays),
            ];
        }

        if (is_int($warrantyMonths) && $warrantyMonths < 12) {
            $risks[] = [
                'level' => 'medium',
                'message' => sprintf('Short warranty detected (%d months).', $warrantyMonths),
            ];
        }

        return $risks;
    }
}
