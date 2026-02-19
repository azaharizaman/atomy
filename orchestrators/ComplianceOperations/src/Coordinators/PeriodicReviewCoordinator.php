<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Coordinators;

use Nexus\ComplianceOperations\Contracts\AmlScreeningAdapterInterface;
use Nexus\ComplianceOperations\Contracts\KycVerificationAdapterInterface;
use Nexus\ComplianceOperations\Contracts\SanctionsCheckAdapterInterface;
use Nexus\ComplianceOperations\DTOs\SagaContext;
use Nexus\ComplianceOperations\Workflows\PeriodicReview\PeriodicReviewWorkflow;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates periodic compliance reviews for existing parties.
 *
 * This coordinator manages the periodic re-verification of existing parties,
 * including review triggering, identity and document reverification,
 * risk reassessment, and status updates.
 *
 * Following the Advanced Orchestrator Pattern:
 * - Coordinators direct flow, they do not execute business logic
 * - Delegates to workflows for stateful operations
 * - Uses adapters for external service integration
 *
 * @see ARCHITECTURE.md Section 3 for coordinator patterns
 */
final readonly class PeriodicReviewCoordinator
{
    public function __construct(
        private PeriodicReviewWorkflow $workflow,
        private KycVerificationAdapterInterface $kycAdapter,
        private AmlScreeningAdapterInterface $amlAdapter,
        private SanctionsCheckAdapterInterface $sanctionsAdapter,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Initiate a periodic review for a party.
     *
     * @param string $tenantId Tenant identifier
     * @param string $userId User initiating the review
     * @param string $partyId Party identifier
     * @param string $partyType Party type (customer, vendor, employee)
     * @param string|null $triggerReason Reason for the review
     * @param array<string, mixed> $additionalContext Additional context data
     * @return array<string, mixed> Review result
     */
    public function initiateReview(
        string $tenantId,
        string $userId,
        string $partyId,
        string $partyType,
        ?string $triggerReason = null,
        array $additionalContext = []
    ): array {
        $this->logger->info('Initiating periodic review', [
            'tenant_id' => $tenantId,
            'party_id' => $partyId,
            'party_type' => $partyType,
        ]);

        try {
            // Get current status for comparison
            $currentRiskLevel = $this->amlAdapter->getRiskLevel($partyId) ?? 'low';
            $currentComplianceStatus = $this->kycAdapter->getStatus($partyId) ?? 'unknown';

            // Build workflow context
            $contextData = array_merge([
                'party_id' => $partyId,
                'party_type' => $partyType,
                'trigger_reason' => $triggerReason ?? 'scheduled',
                'current_risk_level' => $currentRiskLevel,
                'current_compliance_status' => $currentComplianceStatus,
                'initiated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ], $additionalContext);

            $sagaContext = new SagaContext(
                tenantId: $tenantId,
                userId: $userId,
                data: $contextData,
            );

            // Execute the review workflow
            $sagaResult = $this->workflow->execute($sagaContext);

            return [
                'success' => $sagaResult->isSuccessful(),
                'saga_id' => $sagaResult->sagaId,
                'instance_id' => $sagaResult->instanceId,
                'party_id' => $partyId,
                'status' => $sagaResult->status->value,
                'completed_steps' => $sagaResult->completedSteps,
                'failed_step' => $sagaResult->failedStep,
                'error_message' => $sagaResult->errorMessage,
                'message' => $sagaResult->isSuccessful()
                    ? 'Periodic review completed successfully'
                    : 'Periodic review failed',
                'data' => $sagaResult->data,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to initiate periodic review', [
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'party_id' => $partyId,
                'status' => 'failed',
                'message' => 'Failed to initiate periodic review',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the current status of a review workflow.
     *
     * @param string $instanceId Saga instance identifier
     * @return array<string, mixed> Workflow status
     */
    public function getReviewStatus(string $instanceId): array
    {
        $this->logger->info('Getting review status', ['instance_id' => $instanceId]);

        try {
            $state = $this->workflow->getState($instanceId);

            if ($state === null) {
                return [
                    'success' => false,
                    'message' => 'Review workflow not found',
                    'instance_id' => $instanceId,
                ];
            }

            return [
                'success' => true,
                'instance_id' => $instanceId,
                'saga_id' => $state->getSagaId(),
                'tenant_id' => $state->getTenantId(),
                'status' => $state->getStatus()->value,
                'completed_steps' => $state->getCompletedSteps(),
                'compensated_steps' => $state->getCompensatedSteps(),
                'context_data' => $state->getContextData(),
                'step_data' => $state->getStepData(),
                'error_message' => $state->getErrorMessage(),
                'is_terminal' => $state->isTerminal(),
                'created_at' => $state->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updated_at' => $state->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get review status', [
                'instance_id' => $instanceId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'instance_id' => $instanceId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get parties due for periodic review.
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable|null $asOfDate Date to check against
     * @param int $limit Maximum number of parties to return
     * @return array<string, mixed> Parties due for review
     */
    public function getPartiesDueForReview(
        string $tenantId,
        ?\DateTimeImmutable $asOfDate = null,
        int $limit = 100
    ): array {
        $this->logger->info('Getting parties due for review', ['tenant_id' => $tenantId]);

        $asOfDate ??= new \DateTimeImmutable();

        // This would typically query a data provider for actual parties
        // For now, return a placeholder structure
        return [
            'tenant_id' => $tenantId,
            'as_of_date' => $asOfDate->format(\DateTimeInterface::ATOM),
            'limit' => $limit,
            'parties' => [],
            'total_count' => 0,
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Calculate next review date for a party based on risk level.
     *
     * @param string $partyId Party identifier
     * @return array<string, mixed> Next review information
     */
    public function calculateNextReviewDate(string $partyId): array
    {
        $this->logger->info('Calculating next review date', ['party_id' => $partyId]);

        $riskLevel = $this->amlAdapter->getRiskLevel($partyId) ?? 'low';
        $nextReviewDate = $this->amlAdapter->calculateNextReviewDate($riskLevel);

        return [
            'party_id' => $partyId,
            'current_risk_level' => $riskLevel,
            'next_review_date' => $nextReviewDate->format(\DateTimeInterface::ATOM),
            'calculated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Batch initiate reviews for multiple parties.
     *
     * @param string $tenantId Tenant identifier
     * @param string $userId User initiating the reviews
     * @param array<int, array<string, mixed>> $parties Parties to review
     * @return array<string, mixed> Batch review result
     */
    public function batchInitiateReviews(
        string $tenantId,
        string $userId,
        array $parties
    ): array {
        $this->logger->info('Starting batch periodic review', [
            'tenant_id' => $tenantId,
            'party_count' => count($parties),
        ]);

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($parties as $party) {
            $partyId = $party['party_id'] ?? 'unknown';

            try {
                $result = $this->initiateReview(
                    tenantId: $tenantId,
                    userId: $userId,
                    partyId: $partyId,
                    partyType: $party['party_type'] ?? 'customer',
                    triggerReason: $party['trigger_reason'] ?? null,
                    additionalContext: $party['additional_context'] ?? []
                );

                $results[] = $result;

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } catch (\Throwable $e) {
                $results[] = [
                    'success' => false,
                    'party_id' => $partyId,
                    'error' => $e->getMessage(),
                ];
                $failureCount++;
            }
        }

        return [
            'success' => $failureCount === 0,
            'tenant_id' => $tenantId,
            'total_parties' => count($parties),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results,
            'processed_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Get review history for a party.
     *
     * @param string $partyId Party identifier
     * @param int $limit Maximum number of reviews to return
     * @return array<string, mixed> Review history
     */
    public function getReviewHistory(string $partyId, int $limit = 10): array
    {
        $this->logger->info('Getting review history', ['party_id' => $partyId]);

        // This would typically query a data provider for actual history
        return [
            'party_id' => $partyId,
            'limit' => $limit,
            'reviews' => [],
            'total_count' => 0,
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Check if a party requires an immediate review.
     *
     * @param string $partyId Party identifier
     * @return array<string, mixed> Review requirement check result
     */
    public function requiresImmediateReview(string $partyId): array
    {
        $this->logger->info('Checking if immediate review required', ['party_id' => $partyId]);

        $reasons = [];

        // Check for sanctions matches
        if ($this->sanctionsAdapter->hasMatches($partyId)) {
            $reasons[] = 'Party has new sanctions matches';
        }

        // Check for high AML risk
        if ($this->amlAdapter->isHighRisk($partyId)) {
            $reasons[] = 'Party is classified as high AML risk';
        }

        // Check if EDD required
        if ($this->amlAdapter->requiresEdd($partyId)) {
            $reasons[] = 'Party requires enhanced due diligence';
        }

        // Check KYC status
        $kycStatus = $this->kycAdapter->getStatus($partyId);
        if ($kycStatus === 'expired' || $kycStatus === 'rejected') {
            $reasons[] = "KYC status is {$kycStatus}";
        }

        return [
            'party_id' => $partyId,
            'requires_immediate_review' => count($reasons) > 0,
            'reasons' => $reasons,
            'checked_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }
}
