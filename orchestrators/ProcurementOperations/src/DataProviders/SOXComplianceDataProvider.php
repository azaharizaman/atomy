<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DataProviders;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\SOXControlServiceInterface;
use Nexus\ProcurementOperations\Contracts\SOXPerformanceMonitorInterface;
use Nexus\ProcurementOperations\DTOs\SOX\SOXPerformanceMetrics;
use Nexus\ProcurementOperations\Enums\P2PStep;
use Nexus\ProcurementOperations\Enums\SOXControlPoint;
use Nexus\ProcurementOperations\Enums\SOXControlResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Aggregates SOX compliance data from multiple sources.
 *
 * This DataProvider follows the Advanced Orchestrator Pattern v1.1:
 * - Aggregates cross-package data into context DTOs
 * - Prevents coordinators from knowing package intricacies
 * - Provides unified views of compliance status
 */
final readonly class SOXComplianceDataProvider
{
    public function __construct(
        private SOXControlServiceInterface $soxControlService,
        private SOXPerformanceMonitorInterface $performanceMonitor,
        private SOXComplianceStorageInterface $storage,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Get comprehensive SOX compliance context for a tenant.
     *
     * @param string $tenantId
     * @return SOXComplianceContext
     */
    public function getComplianceContext(string $tenantId): SOXComplianceContext
    {
        $this->logger->debug('Building SOX compliance context', ['tenant_id' => $tenantId]);

        // Get configuration
        $isEnabled = $this->soxControlService->isSOXComplianceEnabled($tenantId);
        $enabledControls = $this->storage->getEnabledControls($tenantId);
        $riskProfile = $this->storage->getTenantRiskProfile($tenantId);

        // Get recent performance metrics
        $thirtyDaysAgo = (new \DateTimeImmutable())->modify('-30 days');
        $now = new \DateTimeImmutable();
        $performanceMetrics = $this->performanceMonitor->getMetrics($tenantId, $thirtyDaysAgo, $now);

        // Get pending overrides
        $pendingOverrides = $this->soxControlService->getPendingOverrides($tenantId);

        // Get compliance score
        $complianceScore = $this->calculateComplianceScore(
            $tenantId,
            $performanceMetrics,
            count($pendingOverrides),
        );

        // Get control status summary
        $controlStatusSummary = $this->getControlStatusSummary($tenantId, $thirtyDaysAgo, $now);

        return new SOXComplianceContext(
            tenantId: $tenantId,
            isSOXEnabled: $isEnabled,
            enabledControls: $enabledControls,
            riskProfile: $riskProfile,
            performanceMetrics: $performanceMetrics,
            pendingOverrideCount: count($pendingOverrides),
            complianceScore: $complianceScore,
            controlStatusSummary: $controlStatusSummary,
            lastAssessmentDate: $this->storage->getLastAssessmentDate($tenantId),
        );
    }

    /**
     * Get compliance context for a specific P2P step.
     *
     * @param string $tenantId
     * @param P2PStep $step
     * @return SOXStepComplianceContext
     */
    public function getStepComplianceContext(string $tenantId, P2PStep $step): SOXStepComplianceContext
    {
        // Get controls applicable to this step
        $stepControls = SOXControlPoint::getControlsForStep($step);

        // Get enabled controls for this step
        $enabledControls = array_filter(
            $stepControls,
            fn (SOXControlPoint $control) => $this->storage->isControlEnabled($tenantId, $control),
        );

        // Get recent validation results for these controls
        $thirtyDaysAgo = (new \DateTimeImmutable())->modify('-30 days');
        $now = new \DateTimeImmutable();

        $validationResults = $this->storage->getValidationResults(
            $tenantId,
            array_map(fn ($c) => $c->value, $enabledControls),
            $thirtyDaysAgo,
            $now,
        );

        // Calculate step-specific metrics
        $passCount = 0;
        $failCount = 0;
        $overrideCount = 0;

        foreach ($validationResults as $result) {
            match ($result['result']) {
                SOXControlResult::PASSED->value => $passCount++,
                SOXControlResult::FAILED->value => $failCount++,
                SOXControlResult::OVERRIDDEN->value => $overrideCount++,
                default => null,
            };
        }

        $totalValidations = $passCount + $failCount + $overrideCount;
        $passRate = $totalValidations > 0 ? $passCount / $totalValidations : 0.0;

        return new SOXStepComplianceContext(
            tenantId: $tenantId,
            step: $step,
            applicableControls: $stepControls,
            enabledControls: array_values($enabledControls),
            totalValidations: $totalValidations,
            passCount: $passCount,
            failCount: $failCount,
            overrideCount: $overrideCount,
            passRate: $passRate,
            highRiskControlsEnabled: $this->hasHighRiskControlsEnabled($enabledControls),
        );
    }

    /**
     * Get compliance dashboard data.
     *
     * @param string $tenantId
     * @return SOXComplianceDashboard
     */
    public function getComplianceDashboard(string $tenantId): SOXComplianceDashboard
    {
        $context = $this->getComplianceContext($tenantId);

        // Get trend data
        $weeklyTrends = $this->getWeeklyTrends($tenantId, 12); // 12 weeks

        // Get top failing controls
        $topFailingControls = $this->getTopFailingControls($tenantId, 10);

        // Get override usage
        $overrideUsage = $this->getOverrideUsage($tenantId);

        // Get risk areas
        $riskAreas = $this->identifyRiskAreas($tenantId);

        // Get recommendations
        $recommendations = $this->generateRecommendations($context, $riskAreas);

        return new SOXComplianceDashboard(
            context: $context,
            weeklyTrends: $weeklyTrends,
            topFailingControls: $topFailingControls,
            overrideUsage: $overrideUsage,
            riskAreas: $riskAreas,
            recommendations: $recommendations,
        );
    }

    /**
     * Get control history for audit purposes.
     *
     * @param string $tenantId
     * @param SOXControlPoint $control
     * @param \DateTimeImmutable $from
     * @param \DateTimeImmutable $to
     * @return array<SOXControlHistoryEntry>
     */
    public function getControlHistory(
        string $tenantId,
        SOXControlPoint $control,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array {
        $rawHistory = $this->storage->getControlHistory($tenantId, $control->value, $from, $to);

        return array_map(
            fn (array $entry) => new SOXControlHistoryEntry(
                controlPoint: SOXControlPoint::from($entry['control_point']),
                result: SOXControlResult::from($entry['result']),
                entityType: $entry['entity_type'],
                entityId: $entry['entity_id'],
                userId: $entry['user_id'],
                timestamp: new \DateTimeImmutable($entry['timestamp']),
                details: $entry['details'] ?? [],
                overrideId: $entry['override_id'] ?? null,
            ),
            $rawHistory,
        );
    }

    /**
     * Calculate compliance score (0-100).
     */
    private function calculateComplianceScore(
        string $tenantId,
        SOXPerformanceMetrics $metrics,
        int $pendingOverrideCount,
    ): float {
        $score = 100.0;

        // Deduct for failure rate (max 40 points)
        $score -= min(40.0, $metrics->failureRate * 100 * 4);

        // Deduct for timeout rate (max 10 points)
        $score -= min(10.0, $metrics->timeoutRate * 100 * 10);

        // Deduct for pending overrides (max 10 points)
        $score -= min(10.0, $pendingOverrideCount * 2);

        // Deduct for high P95 latency (max 10 points)
        if ($metrics->p95LatencyMs > 500) {
            $score -= 10.0;
        } elseif ($metrics->p95LatencyMs > 200) {
            $score -= 5.0;
        }

        // Deduct for high-risk control failures
        $highRiskFailures = $this->storage->getHighRiskFailureCount(
            $tenantId,
            (new \DateTimeImmutable())->modify('-30 days'),
            new \DateTimeImmutable(),
        );

        $score -= min(30.0, $highRiskFailures * 5);

        return max(0.0, round($score, 1));
    }

    /**
     * Get control status summary by P2P step.
     *
     * @return array<string, array{enabled: int, disabled: int, pass_rate: float}>
     */
    private function getControlStatusSummary(
        string $tenantId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array {
        $summary = [];

        foreach (P2PStep::cases() as $step) {
            $stepControls = SOXControlPoint::getControlsForStep($step);
            $enabledCount = 0;
            $disabledCount = 0;
            $totalPass = 0;
            $totalValidations = 0;

            foreach ($stepControls as $control) {
                if ($this->storage->isControlEnabled($tenantId, $control)) {
                    $enabledCount++;

                    $stats = $this->storage->getControlStats($tenantId, $control->value, $from, $to);
                    $totalPass += $stats['passed'] ?? 0;
                    $totalValidations += ($stats['total'] ?? 0);
                } else {
                    $disabledCount++;
                }
            }

            $summary[$step->value] = [
                'enabled' => $enabledCount,
                'disabled' => $disabledCount,
                'pass_rate' => $totalValidations > 0 ? $totalPass / $totalValidations : 0.0,
            ];
        }

        return $summary;
    }

    /**
     * Get weekly trend data.
     *
     * @return array<array{week: string, pass_rate: float, failure_count: int, override_count: int}>
     */
    private function getWeeklyTrends(string $tenantId, int $weeks): array
    {
        $trends = [];
        $now = new \DateTimeImmutable();

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = $now->modify("-{$i} weeks")->modify('monday this week');
            $weekEnd = $weekStart->modify('+6 days');

            $stats = $this->storage->getWeeklyStats($tenantId, $weekStart, $weekEnd);

            $trends[] = [
                'week' => $weekStart->format('Y-W'),
                'week_label' => $weekStart->format('M d'),
                'pass_rate' => $stats['pass_rate'] ?? 0.0,
                'failure_count' => $stats['failures'] ?? 0,
                'override_count' => $stats['overrides'] ?? 0,
            ];
        }

        return $trends;
    }

    /**
     * Get top failing controls.
     *
     * @return array<array{control: string, failure_count: int, failure_rate: float}>
     */
    private function getTopFailingControls(string $tenantId, int $limit): array
    {
        $thirtyDaysAgo = (new \DateTimeImmutable())->modify('-30 days');
        $now = new \DateTimeImmutable();

        return $this->storage->getTopFailingControls($tenantId, $thirtyDaysAgo, $now, $limit);
    }

    /**
     * Get override usage statistics.
     *
     * @return array{total: int, by_control: array<string, int>, by_user: array<string, int>}
     */
    private function getOverrideUsage(string $tenantId): array
    {
        $thirtyDaysAgo = (new \DateTimeImmutable())->modify('-30 days');
        $now = new \DateTimeImmutable();

        return $this->storage->getOverrideUsage($tenantId, $thirtyDaysAgo, $now);
    }

    /**
     * Identify risk areas.
     *
     * @return array<string>
     */
    private function identifyRiskAreas(string $tenantId): array
    {
        $risks = [];

        $thirtyDaysAgo = (new \DateTimeImmutable())->modify('-30 days');
        $now = new \DateTimeImmutable();

        // Check for high failure rates on critical controls
        $criticalControls = [
            SOXControlPoint::INV_THREE_WAY_MATCH,
            SOXControlPoint::PAY_DUAL_APPROVAL,
            SOXControlPoint::REQ_SOD_CHECK,
        ];

        foreach ($criticalControls as $control) {
            $stats = $this->storage->getControlStats($tenantId, $control->value, $thirtyDaysAgo, $now);
            $total = $stats['total'] ?? 0;
            $failed = $stats['failed'] ?? 0;

            if ($total > 0 && ($failed / $total) > 0.1) {
                $risks[] = "High failure rate on {$control->value} ({$failed}/{$total})";
            }
        }

        // Check for excessive overrides
        $overrideUsage = $this->getOverrideUsage($tenantId);
        if ($overrideUsage['total'] > 50) {
            $risks[] = "Excessive override usage ({$overrideUsage['total']} in last 30 days)";
        }

        // Check for disabled high-risk controls
        foreach ($criticalControls as $control) {
            if (!$this->storage->isControlEnabled($tenantId, $control)) {
                $risks[] = "Critical control {$control->value} is disabled";
            }
        }

        return $risks;
    }

    /**
     * Generate recommendations.
     *
     * @return array<string>
     */
    private function generateRecommendations(SOXComplianceContext $context, array $riskAreas): array
    {
        $recommendations = [];

        if ($context->complianceScore < 70) {
            $recommendations[] = 'Overall compliance score is below target. Review failing controls and implement corrective actions.';
        }

        if ($context->performanceMetrics->p95LatencyMs > 200) {
            $recommendations[] = 'SOX validation latency is high. Consider optimizing database queries or enabling control caching.';
        }

        if ($context->pendingOverrideCount > 10) {
            $recommendations[] = 'Multiple pending override requests. Expedite approval workflow to reduce backlog.';
        }

        foreach ($context->controlStatusSummary as $step => $summary) {
            if ($summary['pass_rate'] < 0.9 && $summary['enabled'] > 0) {
                $recommendations[] = "Review validation rules for {$step} step - pass rate below 90%.";
            }
        }

        if (count($riskAreas) > 3) {
            $recommendations[] = 'Multiple risk areas identified. Schedule compliance review meeting with stakeholders.';
        }

        return $recommendations;
    }

    /**
     * Check if any high-risk controls are enabled.
     *
     * @param array<SOXControlPoint> $controls
     */
    private function hasHighRiskControlsEnabled(array $controls): bool
    {
        foreach ($controls as $control) {
            if ($control->getRiskLevel() >= 4) {
                return true;
            }
        }
        return false;
    }
}

/**
 * Comprehensive SOX compliance context DTO.
 */
final readonly class SOXComplianceContext
{
    /**
     * @param array<string> $enabledControls
     * @param array<string, array{enabled: int, disabled: int, pass_rate: float}> $controlStatusSummary
     */
    public function __construct(
        public string $tenantId,
        public bool $isSOXEnabled,
        public array $enabledControls,
        public string $riskProfile,
        public SOXPerformanceMetrics $performanceMetrics,
        public int $pendingOverrideCount,
        public float $complianceScore,
        public array $controlStatusSummary,
        public ?\DateTimeImmutable $lastAssessmentDate,
    ) {}
}

/**
 * Step-specific compliance context.
 */
final readonly class SOXStepComplianceContext
{
    /**
     * @param array<SOXControlPoint> $applicableControls
     * @param array<SOXControlPoint> $enabledControls
     */
    public function __construct(
        public string $tenantId,
        public P2PStep $step,
        public array $applicableControls,
        public array $enabledControls,
        public int $totalValidations,
        public int $passCount,
        public int $failCount,
        public int $overrideCount,
        public float $passRate,
        public bool $highRiskControlsEnabled,
    ) {}
}

/**
 * Dashboard data for SOX compliance.
 */
final readonly class SOXComplianceDashboard
{
    /**
     * @param array<array{week: string, pass_rate: float, failure_count: int, override_count: int}> $weeklyTrends
     * @param array<array{control: string, failure_count: int, failure_rate: float}> $topFailingControls
     * @param array{total: int, by_control: array<string, int>, by_user: array<string, int>} $overrideUsage
     * @param array<string> $riskAreas
     * @param array<string> $recommendations
     */
    public function __construct(
        public SOXComplianceContext $context,
        public array $weeklyTrends,
        public array $topFailingControls,
        public array $overrideUsage,
        public array $riskAreas,
        public array $recommendations,
    ) {}
}

/**
 * Single control history entry.
 */
final readonly class SOXControlHistoryEntry
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        public SOXControlPoint $controlPoint,
        public SOXControlResult $result,
        public string $entityType,
        public string $entityId,
        public string $userId,
        public \DateTimeImmutable $timestamp,
        public array $details,
        public ?string $overrideId,
    ) {}
}

/**
 * Storage interface for SOX compliance data (adapter layer).
 */
interface SOXComplianceStorageInterface
{
    /**
     * @return array<string>
     */
    public function getEnabledControls(string $tenantId): array;

    public function isControlEnabled(string $tenantId, SOXControlPoint $control): bool;

    public function getTenantRiskProfile(string $tenantId): string;

    public function getLastAssessmentDate(string $tenantId): ?\DateTimeImmutable;

    /**
     * @param array<string> $controlIds
     * @return array<array{control_point: string, result: string, timestamp: string}>
     */
    public function getValidationResults(
        string $tenantId,
        array $controlIds,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array;

    /**
     * @return array{total: int, passed: int, failed: int}
     */
    public function getControlStats(
        string $tenantId,
        string $controlId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array;

    /**
     * @return array{pass_rate: float, failures: int, overrides: int}
     */
    public function getWeeklyStats(
        string $tenantId,
        \DateTimeImmutable $weekStart,
        \DateTimeImmutable $weekEnd,
    ): array;

    /**
     * @return array<array{control: string, failure_count: int, failure_rate: float}>
     */
    public function getTopFailingControls(
        string $tenantId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        int $limit,
    ): array;

    /**
     * @return array{total: int, by_control: array<string, int>, by_user: array<string, int>}
     */
    public function getOverrideUsage(
        string $tenantId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array;

    /**
     * @return array<array{control_point: string, result: string, entity_type: string, entity_id: string, user_id: string, timestamp: string, details: array, override_id: string|null}>
     */
    public function getControlHistory(
        string $tenantId,
        string $controlId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array;

    public function getHighRiskFailureCount(
        string $tenantId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): int;
}
