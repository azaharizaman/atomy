<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Core\Adapters;

use DateTimeImmutable;
use Nexus\Intelligence\Contracts\AnomalyDetectionServiceInterface;
use Nexus\Intelligence\Contracts\AnomalyResultInterface;
use Nexus\Intelligence\Contracts\FeatureSetInterface;
use Nexus\Intelligence\Enums\SeverityLevel;
use Nexus\Intelligence\ValueObjects\AnomalyResult;

/**
 * Rule-based anomaly detection engine (fallback mode)
 * 
 * Deterministic statistical anomaly detection using Z-scores.
 * Used when external AI providers are unavailable (circuit breaker open).
 */
final class RuleBasedAnomalyEngine implements AnomalyDetectionServiceInterface
{
    /**
     * Z-score thresholds for severity levels
     */
    private const THRESHOLD_CRITICAL = 3.0;
    private const THRESHOLD_HIGH = 2.0;
    private const THRESHOLD_MEDIUM = 1.0;

    public function evaluate(string $processContext, FeatureSetInterface $features): AnomalyResultInterface
    {
        $featuresArray = $features->toArray();
        
        // Extract Z-score if available, otherwise calculate from ratio
        $zScore = $this->extractZScore($featuresArray);
        
        // Determine severity and flagging
        $severity = $this->calculateSeverity($zScore);
        $flagged = abs($zScore) >= self::THRESHOLD_MEDIUM;
        
        // Build reason
        $reason = $this->buildReason($processContext, $zScore, $severity, $featuresArray);
        
        // Always require human review for rule-based decisions
        $requiresReview = true;
        
        // Low confidence for rule-based (0.1)
        $confidence = 0.1;
        
        return new AnomalyResult(
            flagged: $flagged,
            severity: $severity,
            reason: $reason,
            confidenceScore: $confidence,
            calibratedConfidence: $confidence,
            ruleUsed: 'statistical_fallback',
            featureImportance: $this->calculateFeatureImportance($featuresArray, $zScore),
            modelVersion: 'rule-based-v1',
            requiresHumanReview: $requiresReview,
            isAdversarial: false
        );
    }

    /**
     * Extract or calculate Z-score from features
     * 
     * @param array<string, mixed> $features
     * @return float
     */
    private function extractZScore(array $features): float
    {
        // Check for pre-calculated Z-score
        if (isset($features['qty_zscore'])) {
            return (float) $features['qty_zscore'];
        }
        
        // Calculate from ratio if available
        if (isset($features['qty_ratio_to_avg'])) {
            $ratio = (float) $features['qty_ratio_to_avg'];
            // Rough Z-score approximation: (ratio - 1) * 3
            return ($ratio - 1.0) * 3.0;
        }
        
        // Calculate from delta if available
        if (isset($features['qty_delta_from_avg']) && isset($features['historical_std_qty'])) {
            $delta = (float) $features['qty_delta_from_avg'];
            $std = (float) $features['historical_std_qty'];
            return $std > 0 ? $delta / $std : 0.0;
        }
        
        return 0.0;
    }

    /**
     * Calculate severity level from Z-score
     * 
     * @param float $zScore
     * @return SeverityLevel
     */
    private function calculateSeverity(float $zScore): SeverityLevel
    {
        $absZ = abs($zScore);
        
        return match (true) {
            $absZ >= self::THRESHOLD_CRITICAL => SeverityLevel::CRITICAL,
            $absZ >= self::THRESHOLD_HIGH => SeverityLevel::HIGH,
            $absZ >= self::THRESHOLD_MEDIUM => SeverityLevel::MEDIUM,
            default => SeverityLevel::LOW,
        };
    }

    /**
     * Build human-readable reason
     * 
     * @param string $processContext
     * @param float $zScore
     * @param SeverityLevel $severity
     * @param array<string, mixed> $features
     * @return string
     */
    private function buildReason(string $processContext, float $zScore, SeverityLevel $severity, array $features): string
    {
        if (abs($zScore) < self::THRESHOLD_MEDIUM) {
            return "No significant anomaly detected using statistical rules.";
        }
        
        $direction = $zScore > 0 ? 'higher' : 'lower';
        $absZ = abs($zScore);
        
        $reason = "Statistical anomaly detected: Value is {$absZ}σ {$direction} than historical average. ";
        
        // Add context-specific details
        if (isset($features['quantity_ordered']) && isset($features['historical_avg_qty'])) {
            $current = $features['quantity_ordered'];
            $avg = $features['historical_avg_qty'];
            $reason .= "Quantity ordered: {$current} (avg: {$avg}). ";
        }
        
        $reason .= "Severity: {$severity->value}. Rule-based detection active (AI provider unavailable). Human review required.";
        
        return $reason;
    }

    /**
     * Calculate simple feature importance
     * 
     * @param array<string, mixed> $features
     * @param float $zScore
     * @return array<string, float>
     */
    private function calculateFeatureImportance(array $features, float $zScore): array
    {
        $importance = [];
        
        // Assign importance based on presence of key features
        if (isset($features['qty_zscore']) || isset($features['qty_ratio_to_avg'])) {
            $importance['quantity_deviation'] = 1.0;
        }
        
        if (isset($features['vendor_transaction_count'])) {
            $count = (int) $features['vendor_transaction_count'];
            $importance['vendor_history'] = $count < 5 ? 0.7 : 0.3;
        }
        
        if (isset($features['is_new_product']) && $features['is_new_product']) {
            $importance['product_novelty'] = 0.5;
        }
        
        // Sort by importance descending
        arsort($importance);
        
        return $importance;
    }
}
