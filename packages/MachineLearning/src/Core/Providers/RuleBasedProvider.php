<?php

declare(strict_types=1);

namespace Nexus\MachineLearning\Core\Providers;

use Nexus\MachineLearning\Core\Contracts\ProviderInterface;
use Nexus\MachineLearning\Exceptions\FineTuningNotSupportedException;
use Nexus\MachineLearning\ValueObjects\UsageMetrics;

/**
 * Rule-based provider (fallback for when external AI providers are unavailable)
 * 
 * This provider uses deterministic statistical methods and rule-based logic
 * as a fallback when external AI services (OpenAI, Anthropic, Gemini) are down,
 * circuit breaker is open, or no API keys are configured.
 * 
 * Characteristics:
 * - Zero cost (no external API calls)
 * - Deterministic outputs (reproducible results)
 * - Low confidence scores (requires human review)
 * - Statistical methods (Z-scores, thresholds, basic rules)
 * - No fine-tuning support
 * - No feature importance (limited to simple heuristics)
 * 
 * Use cases:
 * - Circuit breaker fallback
 * - Development/testing environments
 * - Offline deployments
 * - Cost-conscious workloads
 */
final class RuleBasedProvider implements ProviderInterface
{
    /**
     * Z-score thresholds for anomaly detection
     */
    private const THRESHOLD_CRITICAL = 3.0;
    private const THRESHOLD_HIGH = 2.0;
    private const THRESHOLD_MEDIUM = 1.0;

    private UsageMetrics $lastUsageMetrics;

    public function __construct()
    {
        $this->lastUsageMetrics = new UsageMetrics(
            tokensUsed: 0,
            costUsd: 0.0,
            latencyMs: 0,
            requestTimestamp: new \DateTimeImmutable()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function sendRequest(array $request): array
    {
        $startTime = microtime(true);

        // Extract task type from request
        $taskType = $request['task_type'] ?? 'unknown';

        // Route to appropriate rule-based handler
        $result = match ($taskType) {
            'anomaly_detection' => $this->handleAnomalyDetection($request),
            'forecasting' => $this->handleForecasting($request),
            'classification' => $this->handleClassification($request),
            'prediction' => $this->handlePrediction($request),
            default => $this->handleGenericTask($request),
        };

        // Track metrics
        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
        $this->lastUsageMetrics = new UsageMetrics(
            tokensUsed: 0, // Rule-based uses no tokens
            costUsd: 0.0, // Always free
            latencyMs: $latencyMs,
            requestTimestamp: new \DateTimeImmutable()
        );

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getUsageMetrics(): UsageMetrics
    {
        return $this->lastUsageMetrics;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsFeatureImportance(): bool
    {
        return false; // Rule-based has limited feature importance
    }

    /**
     * {@inheritDoc}
     */
    public function supportsFineTuning(): bool
    {
        return false; // Rule-based cannot be fine-tuned
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'rule_based';
    }

    /**
     * {@inheritDoc}
     */
    public function getCostPerToken(): float
    {
        return 0.0; // Always free
    }

    /**
     * {@inheritDoc}
     * 
     * @throws FineTuningNotSupportedException Always (rule-based doesn't support fine-tuning)
     */
    public function submitFineTuningJob(array $trainingData, array $config): string
    {
        throw FineTuningNotSupportedException::forProvider($this->getName());
    }

    /**
     * {@inheritDoc}
     * 
     * @throws FineTuningNotSupportedException Always (rule-based doesn't support fine-tuning)
     */
    public function getFineTuningStatus(string $jobId): string
    {
        throw FineTuningNotSupportedException::forProvider($this->getName());
    }

    /**
     * {@inheritDoc}
     * 
     * @throws FineTuningNotSupportedException Always (rule-based doesn't support fine-tuning)
     */
    public function cancelFineTuningJob(string $jobId): void
    {
        throw FineTuningNotSupportedException::forProvider($this->getName());
    }

    /**
     * Handle anomaly detection using statistical methods
     * 
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    private function handleAnomalyDetection(array $request): array
    {
        $features = $request['features'] ?? [];
        $zScore = $this->calculateZScore($features);
        $absZ = abs($zScore);

        $flagged = $absZ >= self::THRESHOLD_MEDIUM;
        $severity = match (true) {
            $absZ >= self::THRESHOLD_CRITICAL => 'critical',
            $absZ >= self::THRESHOLD_HIGH => 'high',
            $absZ >= self::THRESHOLD_MEDIUM => 'medium',
            default => 'low',
        };

        $confidence = 0.1; // Low confidence for rule-based

        return [
            'flagged' => $flagged,
            'severity' => $severity,
            'confidence' => $confidence,
            'z_score' => $zScore,
            'reason' => "Statistical anomaly: {$absZ}σ deviation. Rule-based fallback active.",
            'requires_review' => true,
            'provider' => 'rule_based',
        ];
    }

    /**
     * Handle forecasting using simple moving average
     * 
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    private function handleForecasting(array $request): array
    {
        $historicalData = $request['historical_data'] ?? [];
        $periods = $request['forecast_periods'] ?? 1;

        if (empty($historicalData)) {
            return [
                'forecast' => [],
                'confidence' => 0.0,
                'method' => 'simple_moving_average',
                'provider' => 'rule_based',
            ];
        }

        // Simple moving average
        $avg = array_sum($historicalData) / count($historicalData);
        $forecast = array_fill(0, $periods, $avg);

        return [
            'forecast' => $forecast,
            'confidence' => 0.2, // Low confidence for simple method
            'method' => 'simple_moving_average',
            'provider' => 'rule_based',
        ];
    }

    /**
     * Handle classification using threshold rules
     * 
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    private function handleClassification(array $request): array
    {
        $features = $request['features'] ?? [];
        $threshold = $request['threshold'] ?? 0.5;

        // Simple binary classification based on feature aggregation
        $featureValues = array_values($features);
        $score = !empty($featureValues) ? array_sum($featureValues) / count($featureValues) : 0.0;
        $classification = $score >= $threshold ? 'positive' : 'negative';

        return [
            'classification' => $classification,
            'confidence' => 0.15,
            'score' => $score,
            'provider' => 'rule_based',
        ];
    }

    /**
     * Handle prediction using linear extrapolation
     * 
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    private function handlePrediction(array $request): array
    {
        $features = $request['features'] ?? [];
        
        // Simple linear prediction (average of features)
        $featureValues = array_values($features);
        $prediction = !empty($featureValues) ? array_sum($featureValues) / count($featureValues) : 0.0;

        return [
            'prediction' => $prediction,
            'confidence' => 0.1,
            'method' => 'linear_extrapolation',
            'provider' => 'rule_based',
        ];
    }

    /**
     * Handle generic task (fallback for unknown task types)
     * 
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    private function handleGenericTask(array $request): array
    {
        return [
            'result' => 'Rule-based provider cannot handle this task type',
            'confidence' => 0.0,
            'provider' => 'rule_based',
        ];
    }

    /**
     * Calculate Z-score from features
     * 
     * @param array<string, mixed> $features
     * @return float
     */
    private function calculateZScore(array $features): float
    {
        // Check for pre-calculated Z-score
        if (isset($features['z_score']) || isset($features['qty_zscore'])) {
            return (float) ($features['z_score'] ?? $features['qty_zscore']);
        }

        // Calculate from ratio if available
        if (isset($features['ratio']) || isset($features['qty_ratio_to_avg'])) {
            $ratio = (float) ($features['ratio'] ?? $features['qty_ratio_to_avg']);
            return ($ratio - 1.0) * 3.0; // Rough approximation
        }

        // Calculate from delta and std if available
        if (isset($features['delta'], $features['std_dev'])) {
            $std = (float) $features['std_dev'];
            return $std > 0 ? (float) $features['delta'] / $std : 0.0;
        }

        return 0.0;
    }
}
