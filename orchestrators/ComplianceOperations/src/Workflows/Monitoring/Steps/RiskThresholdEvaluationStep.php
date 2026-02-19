<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\Monitoring\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Risk Threshold Evaluation.
 *
 * Forward action: Evaluates transaction against risk thresholds and velocity limits.
 * Compensation: Clears threshold evaluation results.
 */
final readonly class RiskThresholdEvaluationStep implements SagaStepInterface
{
    public function __construct(
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Get the logger instance, or a NullLogger if none was injected.
     */
    private function getLogger(): LoggerInterface
    {
        return $this->logger ?? new NullLogger();
    }

    public function getId(): string
    {
        return 'risk_threshold_evaluation';
    }

    public function getName(): string
    {
        return 'Risk Threshold Evaluation';
    }

    public function getDescription(): string
    {
        return 'Evaluates transaction against risk thresholds and velocity limits';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting risk threshold evaluation', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $transactionId = $context->get('transaction_id');
            $transactionAmount = $context->get('transaction_amount', 0);
            $transactionCurrency = $context->get('transaction_currency', 'USD');
            $partyId = $context->get('party_id');

            if ($transactionId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Transaction ID is required for threshold evaluation',
                    canRetry: false,
                );
            }

            // Get screening results
            $screeningResult = $context->getStepOutput('transaction_screening');
            $riskIndicators = $screeningResult['risk_indicators'] ?? [];

            // Evaluate against thresholds
            $thresholdResults = $this->evaluateThresholds(
                $transactionAmount,
                $transactionCurrency,
                $partyId,
                $riskIndicators
            );

            // Calculate velocity metrics
            $velocityMetrics = $this->calculateVelocityMetrics($partyId, $transactionAmount);

            // Determine if thresholds are breached
            $thresholdBreached = $thresholdResults['breached'] || $velocityMetrics['exceeded'];

            $evaluationResult = [
                'transaction_id' => $transactionId,
                'evaluation_id' => sprintf('RTE-%s-%s', $transactionId, bin2hex(random_bytes(8))),
                'evaluation_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'threshold_results' => $thresholdResults,
                'velocity_metrics' => $velocityMetrics,
                'threshold_breached' => $thresholdBreached,
                'breach_severity' => $this->determineBreachSeverity($thresholdResults, $velocityMetrics),
                'requires_review' => $thresholdBreached,
                'auto_approve_eligible' => !$thresholdBreached && count($riskIndicators) === 0,
                'risk_score' => $this->calculateRiskScore($thresholdResults, $velocityMetrics, $riskIndicators),
            ];

            $this->getLogger()->info('Risk threshold evaluation completed', [
                'transaction_id' => $transactionId,
                'evaluation_id' => $evaluationResult['evaluation_id'],
                'threshold_breached' => $evaluationResult['threshold_breached'],
                'risk_score' => $evaluationResult['risk_score'],
            ]);

            return SagaStepResult::success($evaluationResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Risk threshold evaluation failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Risk threshold evaluation failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Clearing risk threshold evaluation', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $evaluationId = $context->getStepOutput('risk_threshold_evaluation', 'evaluation_id');

            if ($evaluationId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No risk threshold evaluation to clear',
                ]);
            }

            $this->getLogger()->info('Risk threshold evaluation cleared', [
                'evaluation_id' => $evaluationId,
            ]);

            return SagaStepResult::compensated([
                'voided_evaluation_id' => $evaluationId,
                'reason' => 'Transaction monitoring workflow compensation',
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to clear risk threshold evaluation during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to clear risk threshold evaluation: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 2;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 60; // 1 minute
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }

    /**
     * Evaluate transaction against configured thresholds.
     *
     * @param float $amount Transaction amount
     * @param string $currency Transaction currency
     * @param string|null $partyId Party ID
     * @param array<string> $riskIndicators Risk indicators
     * @return array<string, mixed>
     */
    private function evaluateThresholds(
        float $amount,
        string $currency,
        ?string $partyId,
        array $riskIndicators
    ): array {
        // Threshold configuration (in production, this would come from configuration)
        $thresholds = [
            'single_transaction' => [
                'warning' => 5000,
                'critical' => 10000,
                'blocked' => 50000,
            ],
            'daily_cumulative' => [
                'warning' => 25000,
                'critical' => 50000,
                'blocked' => 250000,
            ],
            'monthly_cumulative' => [
                'warning' => 100000,
                'critical' => 250000,
                'blocked' => 1000000,
            ],
        ];

        $breached = false;
        $breaches = [];

        // Single transaction threshold
        if ($amount >= $thresholds['single_transaction']['blocked']) {
            $breached = true;
            $breaches[] = 'single_transaction_blocked';
        } elseif ($amount >= $thresholds['single_transaction']['critical']) {
            $breached = true;
            $breaches[] = 'single_transaction_critical';
        } elseif ($amount >= $thresholds['single_transaction']['warning']) {
            $breaches[] = 'single_transaction_warning';
        }

        return [
            'breached' => $breached,
            'breaches' => $breaches,
            'thresholds_applied' => $thresholds,
            'amount_evaluated' => $amount,
            'currency' => $currency,
        ];
    }

    /**
     * Calculate velocity metrics for the party.
     *
     * @param string|null $partyId Party ID
     * @param float $currentAmount Current transaction amount
     * @return array<string, mixed>
     */
    private function calculateVelocityMetrics(?string $partyId, float $currentAmount): array
    {
        // In production, this would query actual transaction history
        // Simulated velocity data
        $dailyTotal = $currentAmount + rand(1000, 10000);
        $monthlyTotal = $dailyTotal + rand(10000, 100000);
        $transactionCount24h = rand(1, 10);

        $exceeded = false;
        $velocityFlags = [];

        // Velocity checks
        if ($transactionCount24h > 5) {
            $velocityFlags[] = 'high_frequency_24h';
        }

        if ($dailyTotal > 50000) {
            $exceeded = true;
            $velocityFlags[] = 'daily_limit_exceeded';
        }

        return [
            'exceeded' => $exceeded,
            'velocity_flags' => $velocityFlags,
            'daily_total' => $dailyTotal,
            'monthly_total' => $monthlyTotal,
            'transaction_count_24h' => $transactionCount24h,
        ];
    }

    /**
     * Determine breach severity.
     *
     * @param array<string, mixed> $thresholdResults Threshold results
     * @param array<string, mixed> $velocityMetrics Velocity metrics
     * @return string
     */
    private function determineBreachSeverity(array $thresholdResults, array $velocityMetrics): string
    {
        $breaches = $thresholdResults['breaches'] ?? [];
        $velocityFlags = $velocityMetrics['velocity_flags'] ?? [];

        if (in_array('single_transaction_blocked', $breaches, true)) {
            return 'critical';
        }

        if (in_array('single_transaction_critical', $breaches, true) || $velocityMetrics['exceeded']) {
            return 'high';
        }

        if (in_array('single_transaction_warning', $breaches, true) || !empty($velocityFlags)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Calculate overall risk score.
     *
     * @param array<string, mixed> $thresholdResults Threshold results
     * @param array<string, mixed> $velocityMetrics Velocity metrics
     * @param array<string> $riskIndicators Risk indicators
     * @return int
     */
    private function calculateRiskScore(array $thresholdResults, array $velocityMetrics, array $riskIndicators): int
    {
        $score = 0;

        // Base score from threshold breaches
        $breaches = $thresholdResults['breaches'] ?? [];
        foreach ($breaches as $breach) {
            if (str_contains($breach, 'blocked')) {
                $score += 50;
            } elseif (str_contains($breach, 'critical')) {
                $score += 30;
            } elseif (str_contains($breach, 'warning')) {
                $score += 15;
            }
        }

        // Velocity contribution
        $score += count($velocityMetrics['velocity_flags'] ?? []) * 10;

        // Risk indicators contribution
        $score += count($riskIndicators) * 5;

        return min($score, 100);
    }
}
