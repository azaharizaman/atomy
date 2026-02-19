<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\PeriodicReview\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Risk Reassessment.
 *
 * Forward action: Reassesses party risk based on recent behavior and changes.
 * Compensation: Reverts risk assessment to previous state.
 */
final readonly class RiskReassessmentStep implements SagaStepInterface
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
        return 'risk_reassessment';
    }

    public function getName(): string
    {
        return 'Risk Reassessment';
    }

    public function getDescription(): string
    {
        return 'Reassesses party risk based on recent behavior and changes';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting risk reassessment', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $partyId = $context->get('party_id');
            $currentRiskLevel = $context->get('current_risk_level', 'low');

            if ($partyId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Party ID is required for risk reassessment',
                    canRetry: false,
                );
            }

            // Get previous step results
            $triggerResult = $context->getStepOutput('review_trigger');
            $reverificationResult = $context->getStepOutput('reverification');

            // Perform risk reassessment
            $riskFactors = $this->evaluateRiskFactors($partyId, $reverificationResult);
            $newRiskScore = $this->calculateRiskScore($riskFactors);
            $newRiskLevel = $this->determineRiskLevel($newRiskScore);

            $reassessmentResult = [
                'party_id' => $partyId,
                'reassessment_id' => sprintf('RRA-%s-%s', $partyId, bin2hex(random_bytes(8))),
                'reassessment_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'previous_risk_level' => $currentRiskLevel,
                'previous_risk_score' => $context->get('current_risk_score', 0),
                'new_risk_level' => $newRiskLevel,
                'new_risk_score' => $newRiskScore,
                'risk_level_changed' => $newRiskLevel !== $currentRiskLevel,
                'risk_factors' => $riskFactors,
                'behavioral_analysis' => $this->performBehavioralAnalysis($partyId),
                'transaction_patterns' => $this->analyzeTransactionPatterns($partyId),
                'recommendations' => $this->generateRecommendations($newRiskLevel, $riskFactors),
                'next_review_date' => $this->calculateNextReviewDate($newRiskLevel),
                'requires_enhanced_monitoring' => $newRiskLevel === 'high',
            ];

            $this->getLogger()->info('Risk reassessment completed', [
                'party_id' => $partyId,
                'reassessment_id' => $reassessmentResult['reassessment_id'],
                'previous_risk_level' => $currentRiskLevel,
                'new_risk_level' => $newRiskLevel,
            ]);

            return SagaStepResult::success($reassessmentResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Risk reassessment failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Risk reassessment failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Reverting risk reassessment', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $reassessmentId = $context->getStepOutput('risk_reassessment', 'reassessment_id');

            if ($reassessmentId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No risk reassessment to revert',
                ]);
            }

            // In production, this would revert the risk assessment
            $this->getLogger()->info('Risk reassessment reverted', [
                'reassessment_id' => $reassessmentId,
            ]);

            return SagaStepResult::compensated([
                'voided_reassessment_id' => $reassessmentId,
                'reason' => 'Periodic review workflow compensation',
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to revert risk reassessment during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to revert risk reassessment: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 3;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 180; // 3 minutes
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }

    /**
     * Evaluate risk factors for the party.
     *
     * @param string $partyId Party ID
     * @param array<string, mixed>|null $reverificationResult Reverification results
     * @return array<string, array<string, mixed>>
     */
    private function evaluateRiskFactors(string $partyId, ?array $reverificationResult): array
    {
        // In production, this would analyze actual data
        $factors = [];

        // Document status factor
        $docStatus = 'current';
        if ($reverificationResult !== null) {
            $pendingDocs = $reverificationResult['documents_pending'] ?? 0;
            if ($pendingDocs > 0) {
                $docStatus = 'pending_updates';
            }
        }
        $factors['document_status'] = [
            'status' => $docStatus,
            'score' => $docStatus === 'current' ? 0 : 15,
        ];

        // Transaction behavior factor
        $factors['transaction_behavior'] = [
            'status' => 'normal',
            'score' => 5,
            'anomalies_detected' => 0,
        ];

        // Geographic risk factor
        $factors['geographic_risk'] = [
            'status' => 'low_risk',
            'score' => 5,
            'high_risk_countries' => 0,
        ];

        // Regulatory changes factor
        $factors['regulatory_changes'] = [
            'status' => 'no_impact',
            'score' => 0,
            'applicable_changes' => [],
        ];

        // Time since last review
        $factors['review_timeliness'] = [
            'status' => 'on_schedule',
            'score' => 0,
        ];

        return $factors;
    }

    /**
     * Calculate overall risk score.
     *
     * @param array<string, array<string, mixed>> $riskFactors Risk factors
     * @return int
     */
    private function calculateRiskScore(array $riskFactors): int
    {
        $totalScore = 0;

        foreach ($riskFactors as $factor) {
            $totalScore += $factor['score'] ?? 0;
        }

        return min($totalScore, 100);
    }

    /**
     * Determine risk level from score.
     */
    private function determineRiskLevel(int $score): string
    {
        if ($score >= 60) {
            return 'high';
        }

        if ($score >= 30) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Perform behavioral analysis.
     *
     * @param string $partyId Party ID
     * @return array<string, mixed>
     */
    private function performBehavioralAnalysis(string $partyId): array
    {
        // In production, this would analyze actual behavioral data
        return [
            'status' => 'normal',
            'patterns_analyzed' => [
                'transaction_frequency',
                'transaction_amounts',
                'transaction_counterparties',
                'login_patterns',
            ],
            'anomalies' => [],
            'behavioral_score' => 10,
        ];
    }

    /**
     * Analyze transaction patterns.
     *
     * @param string $partyId Party ID
     * @return array<string, mixed>
     */
    private function analyzeTransactionPatterns(string $partyId): array
    {
        // In production, this would analyze actual transaction data
        return [
            'total_transactions' => rand(50, 500),
            'total_volume' => rand(10000, 1000000),
            'average_transaction_size' => rand(100, 10000),
            'unique_counterparties' => rand(5, 50),
            'high_risk_transactions' => 0,
            'suspicious_patterns' => [],
        ];
    }

    /**
     * Generate recommendations based on risk assessment.
     *
     * @param string $riskLevel Risk level
     * @param array<string, array<string, mixed>> $riskFactors Risk factors
     * @return array<string>
     */
    private function generateRecommendations(string $riskLevel, array $riskFactors): array
    {
        $recommendations = [];

        if ($riskLevel === 'high') {
            $recommendations[] = 'Enhanced due diligence required';
            $recommendations[] = 'Quarterly review cycle recommended';
        }

        if ($riskLevel === 'medium') {
            $recommendations[] = 'Semi-annual review cycle recommended';
        }

        foreach ($riskFactors as $factorName => $factor) {
            if (($factor['score'] ?? 0) > 10) {
                $recommendations[] = "Review {$factorName} for potential issues";
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Continue standard monitoring';
        }

        return $recommendations;
    }

    /**
     * Calculate next review date based on risk level.
     */
    private function calculateNextReviewDate(string $riskLevel): string
    {
        $intervals = [
            'high' => '+3 months',
            'medium' => '+6 months',
            'low' => '+1 year',
        ];

        return (new \DateTimeImmutable())
            ->modify($intervals[$riskLevel] ?? '+1 year')
            ->format('Y-m-d');
    }
}
