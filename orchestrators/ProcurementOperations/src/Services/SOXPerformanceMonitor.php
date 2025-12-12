<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\ProcurementOperations\Contracts\SOXPerformanceMonitorInterface;
use Nexus\ProcurementOperations\DTOs\SOX\SOXPerformanceMetrics;
use Nexus\ProcurementOperations\Enums\SOXControlPoint;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Monitors SOX control validation performance.
 *
 * Tracks latency metrics, failure rates, and provides
 * recommendations for optimization and opt-out eligibility.
 *
 * Uses an injected storage interface for persistence
 * (implemented by adapter layer - Redis, Database, etc.)
 */
final readonly class SOXPerformanceMonitor implements SOXPerformanceMonitorInterface
{
    private const DEFAULT_P95_THRESHOLD_MS = 200.0;
    private const DEFAULT_FAILURE_RATE_THRESHOLD = 0.05; // 5%
    private const MIN_VALIDATIONS_FOR_OPT_OUT = 1000;
    private const OPT_OUT_MAX_FAILURE_RATE = 0.01; // 1%
    private const OPT_OUT_MAX_RISK_SCORE = 2;

    public function __construct(
        private SOXMetricsStorageInterface $storage,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritdoc}
     */
    public function recordValidation(
        string $tenantId,
        SOXControlPoint $controlPoint,
        bool $passed,
        float $durationMs,
        array $metadata = [],
    ): void {
        $record = [
            'tenant_id' => $tenantId,
            'control_point' => $controlPoint->value,
            'passed' => $passed,
            'duration_ms' => $durationMs,
            'metadata' => $metadata,
            'recorded_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ];

        $this->storage->storeValidation($tenantId, $record);

        // Update aggregate counters
        $this->storage->incrementCounter($tenantId, 'total_validations');

        if ($passed) {
            $this->storage->incrementCounter($tenantId, 'passed_validations');
        } else {
            $this->storage->incrementCounter($tenantId, 'failed_validations');
        }

        // Update latency percentiles
        $this->storage->addLatencySample($tenantId, $durationMs);

        $this->logger->debug('SOX validation recorded', $record);
    }

    /**
     * {@inheritdoc}
     */
    public function recordTimeout(
        string $tenantId,
        SOXControlPoint $controlPoint,
        float $durationMs,
    ): void {
        $record = [
            'tenant_id' => $tenantId,
            'control_point' => $controlPoint->value,
            'duration_ms' => $durationMs,
            'recorded_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ];

        $this->storage->storeTimeout($tenantId, $record);
        $this->storage->incrementCounter($tenantId, 'timeouts');

        $this->logger->warning('SOX validation timeout recorded', $record);
    }

    /**
     * {@inheritdoc}
     */
    public function recordError(
        string $tenantId,
        SOXControlPoint $controlPoint,
        string $errorMessage,
    ): void {
        $record = [
            'tenant_id' => $tenantId,
            'control_point' => $controlPoint->value,
            'error_message' => $errorMessage,
            'recorded_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::RFC3339),
        ];

        $this->storage->storeError($tenantId, $record);
        $this->storage->incrementCounter($tenantId, 'errors');

        $this->logger->error('SOX validation error recorded', $record);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetrics(
        string $tenantId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): SOXPerformanceMetrics {
        $stats = $this->storage->getStats($tenantId, $from, $to);

        $totalValidations = $stats['total_validations'] ?? 0;
        $passedValidations = $stats['passed_validations'] ?? 0;
        $failedValidations = $stats['failed_validations'] ?? 0;
        $timeouts = $stats['timeouts'] ?? 0;
        $errors = $stats['errors'] ?? 0;

        $passRate = $totalValidations > 0
            ? $passedValidations / $totalValidations
            : 0.0;

        $failureRate = $totalValidations > 0
            ? $failedValidations / $totalValidations
            : 0.0;

        $timeoutRate = $totalValidations > 0
            ? $timeouts / $totalValidations
            : 0.0;

        // Get latency percentiles
        $latencyPercentiles = $this->storage->getLatencyPercentiles($tenantId, $from, $to);

        // Get breakdown by control point
        $breakdownByControl = $this->storage->getBreakdownByControlPoint($tenantId, $from, $to);

        return new SOXPerformanceMetrics(
            tenantId: $tenantId,
            periodStart: $from,
            periodEnd: $to,
            totalValidations: $totalValidations,
            totalDurationMs: ($latencyPercentiles['total'] ?? 0.0),
            averageDurationMs: ($latencyPercentiles['average'] ?? 0.0),
            p50DurationMs: ($latencyPercentiles['p50'] ?? 0.0),
            p95DurationMs: ($latencyPercentiles['p95'] ?? 0.0),
            p99DurationMs: ($latencyPercentiles['p99'] ?? 0.0),
            maxDurationMs: ($latencyPercentiles['max'] ?? 0.0),
            controlDurations: $breakdownByControl['durations'] ?? [],
            controlInvocations: $breakdownByControl['invocations'] ?? [],
            controlFailures: $breakdownByControl['failures'] ?? [],
            timeoutCount: $timeouts,
            errorCount: $errors,
            failureRate: $failureRate,
            timeoutRate: $timeoutRate,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getP95Latency(string $tenantId, int $days = 30): float
    {
        $from = (new \DateTimeImmutable())->modify("-{$days} days");
        $to = new \DateTimeImmutable();

        $percentiles = $this->storage->getLatencyPercentiles($tenantId, $from, $to);

        return $percentiles['p95'] ?? 0.0;
    }

    /**
     * {@inheritdoc}
     */
    public function assessOptOutEligibility(string $tenantId): array
    {
        $from = (new \DateTimeImmutable())->modify('-90 days');
        $to = new \DateTimeImmutable();

        $stats = $this->storage->getStats($tenantId, $from, $to);

        $totalValidations = $stats['total_validations'] ?? 0;
        $failedValidations = $stats['failed_validations'] ?? 0;

        $reasons = [];
        $riskScore = 0.0;

        // Check 1: Minimum validation count
        if ($totalValidations < self::MIN_VALIDATIONS_FOR_OPT_OUT) {
            $reasons[] = sprintf(
                'Insufficient validation history: %d of %d required',
                $totalValidations,
                self::MIN_VALIDATIONS_FOR_OPT_OUT,
            );
            $riskScore += 2.0;
        }

        // Check 2: Failure rate
        $failureRate = $totalValidations > 0
            ? $failedValidations / $totalValidations
            : 0.0;

        if ($failureRate > self::OPT_OUT_MAX_FAILURE_RATE) {
            $reasons[] = sprintf(
                'Failure rate too high: %.2f%% (max: %.2f%%)',
                $failureRate * 100,
                self::OPT_OUT_MAX_FAILURE_RATE * 100,
            );
            $riskScore += 3.0;
        }

        // Check 3: Recent high-risk control failures
        $highRiskFailures = $this->storage->getHighRiskFailureCount($tenantId, $from, $to);
        if ($highRiskFailures > 0) {
            $reasons[] = sprintf(
                'High-risk control failures detected: %d',
                $highRiskFailures,
            );
            $riskScore += 5.0;
        }

        // Check 4: Override usage
        $overrideCount = $this->storage->getOverrideCount($tenantId, $from, $to);
        if ($overrideCount > 5) {
            $reasons[] = sprintf(
                'Excessive overrides requested: %d',
                $overrideCount,
            );
            $riskScore += 1.0;
        }

        // Normalize risk score to 0-10 scale
        $riskScore = min(10.0, $riskScore);

        $eligible = empty($reasons) && $riskScore <= self::OPT_OUT_MAX_RISK_SCORE;

        if ($eligible) {
            $reasons[] = 'Tenant qualifies for SOX opt-out based on low risk profile';
        }

        return [
            'eligible' => $eligible,
            'reasons' => $reasons,
            'risk_score' => $riskScore,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTenantsWithPerformanceIssues(float $thresholdMs = self::DEFAULT_P95_THRESHOLD_MS): array
    {
        $tenants = $this->storage->getAllTenantIds();
        $issues = [];

        foreach ($tenants as $tenantId) {
            $p95 = $this->getP95Latency($tenantId, 7); // Last 7 days

            if ($p95 > $thresholdMs) {
                $issues[$tenantId] = $p95;
            }
        }

        // Sort by latency descending
        arsort($issues);

        return $issues;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptOutRecommendations(): array
    {
        $tenants = $this->storage->getAllTenantIds();
        $recommendations = [];

        foreach ($tenants as $tenantId) {
            $assessment = $this->assessOptOutEligibility($tenantId);

            if ($assessment['eligible']) {
                $recommendations[$tenantId] = [
                    'risk_score' => $assessment['risk_score'],
                    'reasons' => $assessment['reasons'],
                ];
            }
        }

        return $recommendations;
    }

    /**
     * {@inheritdoc}
     */
    public function clearMetrics(string $tenantId): void
    {
        $this->storage->clearTenantData($tenantId);

        $this->logger->info('SOX metrics cleared', ['tenant_id' => $tenantId]);
    }
}

/**
 * Storage interface for SOX metrics (to be implemented by adapter layer).
 */
interface SOXMetricsStorageInterface
{
    /**
     * @param array<string, mixed> $record
     */
    public function storeValidation(string $tenantId, array $record): void;

    /**
     * @param array<string, mixed> $record
     */
    public function storeTimeout(string $tenantId, array $record): void;

    /**
     * @param array<string, mixed> $record
     */
    public function storeError(string $tenantId, array $record): void;

    public function incrementCounter(string $tenantId, string $counter): void;

    public function addLatencySample(string $tenantId, float $latencyMs): void;

    /**
     * @return array<string, int>
     */
    public function getStats(string $tenantId, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /**
     * @return array{p50: float, p95: float, p99: float, average: float}
     */
    public function getLatencyPercentiles(string $tenantId, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    /**
     * @return array<string, array{total: int, passed: int, failed: int, avg_latency: float}>
     */
    public function getBreakdownByControlPoint(string $tenantId, \DateTimeImmutable $from, \DateTimeImmutable $to): array;

    public function getHighRiskFailureCount(string $tenantId, \DateTimeImmutable $from, \DateTimeImmutable $to): int;

    public function getOverrideCount(string $tenantId, \DateTimeImmutable $from, \DateTimeImmutable $to): int;

    /**
     * @return array<string>
     */
    public function getAllTenantIds(): array;

    public function clearTenantData(string $tenantId): void;
}
