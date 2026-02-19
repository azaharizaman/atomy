<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\Onboarding\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Compliance Approval.
 *
 * Forward action: Records compliance approval decision and creates audit trail.
 * Compensation: Revokes approval and marks party as pending review.
 */
final readonly class ComplianceApprovalStep implements SagaStepInterface
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
        return 'compliance_approval';
    }

    public function getName(): string
    {
        return 'Compliance Approval';
    }

    public function getDescription(): string
    {
        return 'Records compliance approval decision and creates audit trail';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting compliance approval', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $partyId = $context->get('party_id');
            $userId = $context->userId;

            if ($partyId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Party ID is required for compliance approval',
                    canRetry: false,
                );
            }

            // Get risk assessment result
            $riskAssessment = $context->getStepOutput('risk_assessment');

            if ($riskAssessment === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Risk assessment is required before approval',
                    canRetry: false,
                );
            }

            $overallRiskLevel = $riskAssessment['overall_risk_level'] ?? 'unknown';
            $requiresManualApproval = $riskAssessment['requires_manual_approval'] ?? false;

            // Determine approval status
            $approvalStatus = $this->determineApprovalStatus($overallRiskLevel, $requiresManualApproval);

            // If manual approval is required, check if it was provided
            if ($requiresManualApproval) {
                $manualApproval = $context->get('manual_approval');

                if ($manualApproval === null) {
                    return SagaStepResult::failure(
                        errorMessage: 'Manual approval required for high-risk party',
                        canRetry: false,
                    );
                }

                if ($manualApproval['approved'] === false) {
                    return SagaStepResult::failure(
                        errorMessage: 'Manual approval was rejected: ' . ($manualApproval['reason'] ?? 'No reason provided'),
                        canRetry: false,
                    );
                }
            }

            $approvalResult = [
                'party_id' => $partyId,
                'approval_id' => sprintf('CAP-%s-%s', $partyId, bin2hex(random_bytes(8))),
                'approval_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'approval_status' => $approvalStatus,
                'approved_by' => $userId,
                'risk_level_at_approval' => $overallRiskLevel,
                'risk_score_at_approval' => $riskAssessment['overall_risk_score'] ?? 0,
                'requires_enhanced_monitoring' => $riskAssessment['requires_enhanced_due_diligence'] ?? false,
                'next_review_date' => $riskAssessment['next_review_date'] ?? (new \DateTimeImmutable())->modify('+1 year')->format('Y-m-d'),
                'conditions' => $this->generateConditions($overallRiskLevel),
                'audit_trail_id' => sprintf('AUD-%s', bin2hex(random_bytes(16))),
            ];

            $this->getLogger()->info('Compliance approval completed', [
                'party_id' => $partyId,
                'approval_id' => $approvalResult['approval_id'],
                'approval_status' => $approvalResult['approval_status'],
            ]);

            return SagaStepResult::success($approvalResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Compliance approval failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Compliance approval failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Revoking compliance approval', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $approvalId = $context->getStepOutput('compliance_approval', 'approval_id');

            if ($approvalId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No compliance approval to revoke',
                ]);
            }

            // In production, this would revoke the approval and update party status
            $this->getLogger()->info('Compliance approval revoked', [
                'approval_id' => $approvalId,
            ]);

            return SagaStepResult::compensated([
                'revoked_approval_id' => $approvalId,
                'reason' => 'Onboarding workflow compensation',
                'party_status' => 'pending_review',
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to revoke compliance approval during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to revoke compliance approval: ' . $e->getMessage()
            );
        }
    }

    public function hasCompensation(): bool
    {
        return true;
    }

    public function getOrder(): int
    {
        return 5;
    }

    public function isRequired(): bool
    {
        return true;
    }

    public function getTimeout(): int
    {
        return 120; // 2 minutes
    }

    public function getRetryAttempts(): int
    {
        return 2;
    }

    /**
     * Determine approval status based on risk level.
     */
    private function determineApprovalStatus(string $riskLevel, bool $requiresManualApproval): string
    {
        if ($riskLevel === 'high' && !$requiresManualApproval) {
            return 'conditional';
        }

        if ($riskLevel === 'high') {
            return 'approved_with_conditions';
        }

        if ($riskLevel === 'medium') {
            return 'approved_with_monitoring';
        }

        return 'approved';
    }

    /**
     * Generate conditions based on risk level.
     *
     * @return array<string>
     */
    private function generateConditions(string $riskLevel): array
    {
        $conditions = [];

        if ($riskLevel === 'high') {
            $conditions[] = 'Enhanced transaction monitoring required';
            $conditions[] = 'Quarterly compliance review required';
            $conditions[] = 'Transaction limits may apply';
        }

        if ($riskLevel === 'medium') {
            $conditions[] = 'Standard transaction monitoring applies';
            $conditions[] = 'Semi-annual compliance review required';
        }

        if ($riskLevel === 'low') {
            $conditions[] = 'Standard monitoring procedures apply';
        }

        return $conditions;
    }
}
