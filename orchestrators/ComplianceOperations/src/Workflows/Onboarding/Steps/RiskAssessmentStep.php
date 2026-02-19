<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\Onboarding\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Risk Assessment.
 *
 * Forward action: Aggregates risk scores from all checks and determines overall risk level.
 * Compensation: Clears risk assessment and triggers re-assessment requirement.
 */
final readonly class RiskAssessmentStep implements SagaStepInterface
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
        return 'risk_assessment';
    }

    public function getName(): string
    {
        return 'Risk Assessment';
    }

    public function getDescription(): string
    {
        return 'Aggregates risk scores from all checks and determines overall risk level';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting risk assessment', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $partyId = $context->get('party_id');

            if ($partyId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Party ID is required for risk assessment',
                    canRetry: false,
                );
            }

            // Get results from previous steps
            $kycResult = $context->getStepOutput('kyc_verification');
            $amlResult = $context->getStepOutput('aml_screening');
            $sanctionsResult = $context->getStepOutput('sanctions_check');

            // Aggregate risk scores
            $riskFactors = [];
            $totalRiskScore = 0;
            $maxPossibleScore = 100;

            // KYC risk contribution
            if ($kycResult !== null) {
                $kycFlags = $kycResult['risk_flags'] ?? [];
                $kycScore = count($kycFlags) * 10;
                $riskFactors['kyc'] = [
                    'score' => $kycScore,
                    'flags' => $kycFlags,
                    'weight' => 0.25,
                ];
                $totalRiskScore += $kycScore * 0.25;
            }

            // AML risk contribution
            if ($amlResult !== null) {
                $amlScore = $amlResult['risk_score'] ?? 0;
                $riskFactors['aml'] = [
                    'score' => $amlScore,
                    'risk_level' => $amlResult['risk_level'] ?? 'unknown',
                    'weight' => 0.35,
                ];
                $totalRiskScore += $amlScore * 0.35;
            }

            // Sanctions risk contribution
            if ($sanctionsResult !== null) {
                $sanctionsScore = $sanctionsResult['confirmed_matches'] > 0 ? 100 : 0;
                $sanctionsScore += $sanctionsResult['potential_matches'] * 20;
                $riskFactors['sanctions'] = [
                    'score' => min($sanctionsScore, 100),
                    'matches' => $sanctionsResult['confirmed_matches'],
                    'potential_matches' => $sanctionsResult['potential_matches'],
                    'pep_status' => $sanctionsResult['pep_status'],
                    'weight' => 0.40,
                ];
                $totalRiskScore += min($sanctionsScore, 100) * 0.40;
            }

            // Determine overall risk level
            $overallRiskLevel = $this->determineRiskLevel($totalRiskScore);

            $assessmentResult = [
                'party_id' => $partyId,
                'assessment_id' => sprintf('RA-%s-%s', $partyId, bin2hex(random_bytes(8))),
                'assessment_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'overall_risk_score' => round($totalRiskScore, 2),
                'overall_risk_level' => $overallRiskLevel,
                'max_possible_score' => $maxPossibleScore,
                'risk_factors' => $riskFactors,
                'requires_enhanced_due_diligence' => $totalRiskScore > 50,
                'requires_manual_approval' => $totalRiskScore > 70,
                'recommendations' => $this->generateRecommendations($totalRiskScore, $riskFactors),
                'next_review_date' => $this->calculateNextReviewDate($overallRiskLevel),
            ];

            $this->getLogger()->info('Risk assessment completed', [
                'party_id' => $partyId,
                'assessment_id' => $assessmentResult['assessment_id'],
                'overall_risk_score' => $assessmentResult['overall_risk_score'],
                'overall_risk_level' => $assessmentResult['overall_risk_level'],
            ]);

            return SagaStepResult::success($assessmentResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Risk assessment failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Risk assessment failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Clearing risk assessment', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $assessmentId = $context->getStepOutput('risk_assessment', 'assessment_id');

            if ($assessmentId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No risk assessment to clear',
                ]);
            }

            // In production, this would mark the assessment as voided
            $this->getLogger()->info('Risk assessment cleared', [
                'assessment_id' => $assessmentId,
            ]);

            return SagaStepResult::compensated([
                'voided_assessment_id' => $assessmentId,
                'reason' => 'Onboarding workflow compensation',
                'requires_reassessment' => true,
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to clear risk assessment during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to clear risk assessment: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 4;
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
     * Determine overall risk level based on score.
     */
    private function determineRiskLevel(float $score): string
    {
        if ($score >= 70) {
            return 'high';
        }

        if ($score >= 40) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Generate recommendations based on risk assessment.
     *
     * @param float $score Overall risk score
     * @param array<string, array<string, mixed>> $riskFactors Risk factors
     * @return array<string>
     */
    private function generateRecommendations(float $score, array $riskFactors): array
    {
        $recommendations = [];

        if ($score > 50) {
            $recommendations[] = 'Enhanced due diligence required';
        }

        if ($score > 70) {
            $recommendations[] = 'Manual compliance review required';
            $recommendations[] = 'Consider transaction monitoring escalation';
        }

        if (isset($riskFactors['sanctions']) && $riskFactors['sanctions']['pep_status'] !== 'not_pep') {
            $recommendations[] = 'PEP status requires additional documentation';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Standard monitoring procedures apply';
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
