<?php

declare(strict_types=1);

namespace Nexus\QuotationIntelligence\Services;

use Nexus\QuotationIntelligence\Contracts\VendorScoringServiceInterface;
use Nexus\QuotationIntelligence\DTOs\NormalizedQuoteLine;

/**
 * Weighted MCDA scorer with lifecycle-cost-adjusted price dimension.
 */
final readonly class WeightedVendorScoringService implements VendorScoringServiceInterface
{
    private const PRICE_WEIGHT = 0.50;
    private const RISK_WEIGHT = 0.20;
    private const DELIVERY_WEIGHT = 0.15;
    private const SUSTAINABILITY_WEIGHT = 0.15;

    private const DEFAULT_SUSTAINABILITY_SCORE = 50.0;
    private const DEFAULT_DELIVERY_DAYS = 30;

    /**
     * @inheritDoc
     */
    public function score(string $tenantId, string $rfqId, array $vendorEvaluations): array
    {
        $lccTotals = [];
        $deliveryDays = [];
        $dimensionRows = [];

        foreach ($vendorEvaluations as $evaluation) {
            $vendorId = (string)$evaluation['vendor_id'];
            /** @var array<NormalizedQuoteLine> $lines */
            $lines = $evaluation['lines'];
            $risks = is_array($evaluation['risks'] ?? null) ? $evaluation['risks'] : [];

            $lccTotals[$vendorId] = $this->calculateLifecycleCostTotal($lines);
            $deliveryDays[$vendorId] = $this->calculateAverageLeadTimeDays($lines);
            $dimensionRows[$vendorId] = [
                'risk_score' => $this->calculateRiskScore($risks),
                'sustainability_score' => $this->calculateSustainabilityScore($lines),
            ];
        }

        $minLcc = $lccTotals !== [] ? min($lccTotals) : 0.0;
        $minDelivery = $deliveryDays !== [] ? min($deliveryDays) : self::DEFAULT_DELIVERY_DAYS;

        $ranking = [];
        foreach ($vendorEvaluations as $evaluation) {
            $vendorId = (string)$evaluation['vendor_id'];
            $priceScore = $this->calculatePriceScore($lccTotals[$vendorId] ?? 0.0, $minLcc);
            $deliveryScore = $this->calculateDeliveryScore($deliveryDays[$vendorId] ?? self::DEFAULT_DELIVERY_DAYS, $minDelivery);
            $riskScore = (float)$dimensionRows[$vendorId]['risk_score'];
            $sustainabilityScore = (float)$dimensionRows[$vendorId]['sustainability_score'];

            $totalScore =
                ($priceScore * self::PRICE_WEIGHT) +
                ($riskScore * self::RISK_WEIGHT) +
                ($deliveryScore * self::DELIVERY_WEIGHT) +
                ($sustainabilityScore * self::SUSTAINABILITY_WEIGHT);

            $ranking[] = [
                'vendor_id' => $vendorId,
                'rank' => 0,
                'total_score' => round($totalScore, 2),
                'dimensions' => [
                    'price_score' => round($priceScore, 2),
                    'risk_score' => round($riskScore, 2),
                    'delivery_score' => round($deliveryScore, 2),
                    'sustainability_score' => round($sustainabilityScore, 2),
                    'lifecycle_cost_total' => round($lccTotals[$vendorId] ?? 0.0, 2),
                ],
            ];
        }

        usort($ranking, static function (array $a, array $b): int {
            return $b['total_score'] <=> $a['total_score'];
        });

        foreach ($ranking as $index => $row) {
            $ranking[$index]['rank'] = $index + 1;
        }

        return [
            'weights' => [
                'price' => self::PRICE_WEIGHT,
                'risk' => self::RISK_WEIGHT,
                'delivery' => self::DELIVERY_WEIGHT,
                'sustainability' => self::SUSTAINABILITY_WEIGHT,
            ],
            'ranking' => $ranking,
        ];
    }

    /**
     * @param array<NormalizedQuoteLine> $lines
     */
    private function calculateLifecycleCostTotal(array $lines): float
    {
        $total = 0.0;

        foreach ($lines as $line) {
            $multiplier = (float)($line->metadata['lifecycle_multiplier'] ?? 1.0);
            if ($multiplier <= 0.0) {
                $multiplier = 1.0;
            }

            $lineCost = $line->normalizedUnitPrice * $line->normalizedQuantity * $multiplier;
            $total += $lineCost;
        }

        return $total;
    }

    private function calculatePriceScore(float $vendorLccTotal, float $minLcc): float
    {
        if ($vendorLccTotal <= 0.0 || $minLcc <= 0.0) {
            return 0.0;
        }

        $score = ($minLcc / $vendorLccTotal) * 100.0;
        return max(0.0, min(100.0, $score));
    }

    /**
     * @param array<int, array<string, mixed>> $risks
     */
    private function calculateRiskScore(array $risks): float
    {
        $penalty = 0.0;
        foreach ($risks as $risk) {
            $level = strtolower((string)($risk['level'] ?? ''));
            if ($level === 'high') {
                $penalty += 20.0;
                continue;
            }

            if ($level === 'medium') {
                $penalty += 10.0;
                continue;
            }

            if ($level === 'low') {
                $penalty += 5.0;
            }
        }

        $score = 100.0 - $penalty;
        return max(0.0, min(100.0, $score));
    }

    /**
     * @param array<NormalizedQuoteLine> $lines
     */
    private function calculateAverageLeadTimeDays(array $lines): int
    {
        $leadTimeDays = [];
        foreach ($lines as $line) {
            $commercialTerms = is_array($line->metadata['commercial_terms'] ?? null) ? $line->metadata['commercial_terms'] : [];
            $days = $commercialTerms['lead_time_days'] ?? null;
            if (is_int($days) && $days > 0) {
                $leadTimeDays[] = $days;
            }
        }

        if ($leadTimeDays === []) {
            return self::DEFAULT_DELIVERY_DAYS;
        }

        return (int)round(array_sum($leadTimeDays) / count($leadTimeDays));
    }

    private function calculateDeliveryScore(int $vendorDays, int $minDays): float
    {
        if ($vendorDays <= 0 || $minDays <= 0) {
            return 0.0;
        }

        $score = ($minDays / $vendorDays) * 100.0;
        return max(0.0, min(100.0, $score));
    }

    /**
     * @param array<NormalizedQuoteLine> $lines
     */
    private function calculateSustainabilityScore(array $lines): float
    {
        $scores = [];

        foreach ($lines as $line) {
            $score = $line->metadata['sustainability_score'] ?? null;
            if (is_numeric($score)) {
                $numeric = (float)$score;
                $scores[] = max(0.0, min(100.0, $numeric));
            }
        }

        if ($scores === []) {
            return self::DEFAULT_SUSTAINABILITY_SCORE;
        }

        return array_sum($scores) / count($scores);
    }
}

