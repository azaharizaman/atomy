<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\PeriodicReview\Steps;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\DTOs\SagaStepContext;
use Nexus\ComplianceOperations\DTOs\SagaStepResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Saga step: Status Update.
 *
 * Forward action: Updates party compliance status based on review results.
 * Compensation: Reverts status to previous state.
 */
final readonly class StatusUpdateStep implements SagaStepInterface
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
        return 'status_update';
    }

    public function getName(): string
    {
        return 'Status Update';
    }

    public function getDescription(): string
    {
        return 'Updates party compliance status based on review results';
    }

    public function execute(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Starting status update', [
            'saga_instance_id' => $context->sagaInstanceId,
            'tenant_id' => $context->tenantId,
        ]);

        try {
            $partyId = $context->get('party_id');
            $currentStatus = $context->get('current_compliance_status', 'active');

            if ($partyId === null) {
                return SagaStepResult::failure(
                    errorMessage: 'Party ID is required for status update',
                    canRetry: false,
                );
            }

            // Get previous step results
            $triggerResult = $context->getStepOutput('review_trigger');
            $reverificationResult = $context->getStepOutput('reverification');
            $riskReassessmentResult = $context->getStepOutput('risk_reassessment');

            // Determine new status
            $newStatus = $this->determineNewStatus(
                $reverificationResult,
                $riskReassessmentResult
            );

            // Generate audit trail
            $auditTrail = $this->generateAuditTrail(
                $partyId,
                $currentStatus,
                $newStatus,
                $context->userId
            );

            $statusUpdateResult = [
                'party_id' => $partyId,
                'update_id' => sprintf('SUP-%s-%s', $partyId, bin2hex(random_bytes(8))),
                'update_timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'previous_status' => $currentStatus,
                'new_status' => $newStatus,
                'status_changed' => $newStatus !== $currentStatus,
                'review_type' => $triggerResult['review_type'] ?? 'standard',
                'new_risk_level' => $riskReassessmentResult['new_risk_level'] ?? 'low',
                'next_review_date' => $riskReassessmentResult['next_review_date'] ?? (new \DateTimeImmutable())->modify('+1 year')->format('Y-m-d'),
                'conditions' => $this->generateConditions($newStatus, $riskReassessmentResult),
                'restrictions' => $this->generateRestrictions($newStatus, $riskReassessmentResult),
                'audit_trail' => $auditTrail,
                'notifications_sent' => true,
                'reviewer' => $context->userId,
            ];

            $this->getLogger()->info('Status update completed', [
                'party_id' => $partyId,
                'update_id' => $statusUpdateResult['update_id'],
                'previous_status' => $currentStatus,
                'new_status' => $newStatus,
            ]);

            return SagaStepResult::success($statusUpdateResult);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Status update failed', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::failure(
                errorMessage: 'Status update failed: ' . $e->getMessage(),
                canRetry: true,
            );
        }
    }

    public function compensate(SagaStepContext $context): SagaStepResult
    {
        $this->getLogger()->info('Compensating: Reverting status update', [
            'saga_instance_id' => $context->sagaInstanceId,
        ]);

        try {
            $updateId = $context->getStepOutput('status_update', 'update_id');
            $previousStatus = $context->getStepOutput('status_update', 'previous_status');

            if ($updateId === null) {
                return SagaStepResult::compensated([
                    'message' => 'No status update to revert',
                ]);
            }

            // In production, this would revert the status
            $this->getLogger()->info('Status update reverted', [
                'update_id' => $updateId,
                'reverted_to_status' => $previousStatus,
            ]);

            return SagaStepResult::compensated([
                'voided_update_id' => $updateId,
                'reverted_status' => $previousStatus,
                'reason' => 'Periodic review workflow compensation',
            ]);
        } catch (\Throwable $e) {
            $this->getLogger()->error('Failed to revert status update during compensation', [
                'error' => $e->getMessage(),
            ]);

            return SagaStepResult::compensationFailed(
                'Failed to revert status update: ' . $e->getMessage()
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
        return 60; // 1 minute
    }

    public function getRetryAttempts(): int
    {
        return 3;
    }

    /**
     * Determine new compliance status.
     *
     * @param array<string, mixed>|null $reverificationResult Reverification results
     * @param array<string, mixed>|null $riskReassessmentResult Risk reassessment results
     * @return string
     */
    private function determineNewStatus(
        ?array $reverificationResult,
        ?array $riskReassessmentResult
    ): string {
        // Check for critical issues
        if ($reverificationResult !== null) {
            $overallStatus = $reverificationResult['overall_status'] ?? 'verified';

            if ($overallStatus === 'requires_attention') {
                return 'suspended';
            }

            if ($overallStatus === 'pending_documents') {
                return 'pending_review';
            }
        }

        // Check risk level
        if ($riskReassessmentResult !== null) {
            $newRiskLevel = $riskReassessmentResult['new_risk_level'] ?? 'low';

            if ($newRiskLevel === 'high') {
                return 'restricted';
            }
        }

        return 'active';
    }

    /**
     * Generate conditions for the status.
     *
     * @param string $status New status
     * @param array<string, mixed>|null $riskReassessmentResult Risk reassessment results
     * @return array<string>
     */
    private function generateConditions(string $status, ?array $riskReassessmentResult): array
    {
        $conditions = [];

        if ($status === 'restricted') {
            $conditions[] = 'Transaction limits apply';
            $conditions[] = 'Enhanced monitoring active';
            $conditions[] = 'Manual approval required for high-value transactions';
        }

        if ($status === 'pending_review') {
            $conditions[] = 'Document submission required';
            $conditions[] = 'Temporary transaction restrictions may apply';
        }

        if ($status === 'active') {
            $conditions[] = 'Standard monitoring applies';
        }

        // Add risk-based conditions
        if ($riskReassessmentResult !== null) {
            $recommendations = $riskReassessmentResult['recommendations'] ?? [];
            foreach ($recommendations as $recommendation) {
                if (!in_array($recommendation, $conditions, true)) {
                    $conditions[] = $recommendation;
                }
            }
        }

        return $conditions;
    }

    /**
     * Generate restrictions for the status.
     *
     * @param string $status New status
     * @param array<string, mixed>|null $riskReassessmentResult Risk reassessment results
     * @return array<string, mixed>
     */
    private function generateRestrictions(string $status, ?array $riskReassessmentResult): array
    {
        $restrictions = [];

        if ($status === 'suspended') {
            $restrictions = [
                'transaction_blocked' => true,
                'requires_manual_approval' => true,
                'restricted_until' => 'review_complete',
            ];
        }

        if ($status === 'restricted') {
            $restrictions = [
                'transaction_limit' => 10000,
                'daily_limit' => 25000,
                'requires_enhanced_monitoring' => true,
            ];
        }

        if ($status === 'pending_review') {
            $restrictions = [
                'document_submission_required' => true,
                'temporary_limits' => true,
            ];
        }

        return $restrictions;
    }

    /**
     * Generate audit trail entry.
     *
     * @param string $partyId Party ID
     * @param string $previousStatus Previous status
     * @param string $newStatus New status
     * @param string $userId User performing the update
     * @return array<string, mixed>
     */
    private function generateAuditTrail(
        string $partyId,
        string $previousStatus,
        string $newStatus,
        string $userId
    ): array {
        return [
            'audit_id' => sprintf('AUD-%s', bin2hex(random_bytes(16))),
            'party_id' => $partyId,
            'action' => 'periodic_review_status_update',
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'performed_by' => $userId,
            'performed_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'ip_address' => null, // Would be populated in production
            'user_agent' => null, // Would be populated in production
        ];
    }
}
