<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Coordinators;

use Nexus\ComplianceOperations\Contracts\AmlScreeningAdapterInterface;
use Nexus\ComplianceOperations\Contracts\KycVerificationAdapterInterface;
use Nexus\ComplianceOperations\Contracts\SanctionsCheckAdapterInterface;
use Nexus\ComplianceOperations\DTOs\SagaContext;
use Nexus\ComplianceOperations\Workflows\Onboarding\OnboardingComplianceWorkflow;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates the onboarding compliance workflow for new parties.
 *
 * This coordinator manages the complete onboarding compliance process,
 * orchestrating KYC verification, AML screening, sanctions checks,
 * risk assessment, and compliance approval.
 *
 * Following the Advanced Orchestrator Pattern:
 * - Coordinators direct flow, they do not execute business logic
 * - Delegates to workflows for stateful operations
 * - Uses adapters for external service integration
 *
 * @see ARCHITECTURE.md Section 3 for coordinator patterns
 */
final readonly class OnboardingCoordinator
{
    public function __construct(
        private OnboardingComplianceWorkflow $workflow,
        private KycVerificationAdapterInterface $kycAdapter,
        private AmlScreeningAdapterInterface $amlAdapter,
        private SanctionsCheckAdapterInterface $sanctionsAdapter,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Initiate onboarding compliance for a new party.
     *
     * @param string $tenantId Tenant identifier
     * @param string $userId User initiating the onboarding
     * @param string $partyId Party identifier
     * @param string $partyType Party type (customer, vendor, employee)
     * @param string $partyName Party name
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @param array<string, mixed> $additionalContext Additional context data
     * @return array<string, mixed> Onboarding result
     */
    public function initiateOnboarding(
        string $tenantId,
        string $userId,
        string $partyId,
        string $partyType,
        string $partyName,
        string $countryCode,
        array $additionalContext = []
    ): array {
        $this->logger->info('Initiating onboarding compliance', [
            'tenant_id' => $tenantId,
            'party_id' => $partyId,
            'party_type' => $partyType,
        ]);

        try {
            // Build workflow context
            $contextData = array_merge([
                'party_id' => $partyId,
                'party_type' => $partyType,
                'party_name' => $partyName,
                'country_code' => $countryCode,
                'initiated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ], $additionalContext);

            $sagaContext = new SagaContext(
                tenantId: $tenantId,
                userId: $userId,
                data: $contextData,
            );

            // Execute the workflow
            $sagaResult = $this->workflow->execute($sagaContext);

            return [
                'success' => $sagaResult->isSuccessful(),
                'saga_id' => $sagaResult->sagaId,
                'instance_id' => $sagaResult->instanceId,
                'party_id' => $partyId,
                'status' => $sagaResult->status->value,
                'completed_steps' => $sagaResult->completedSteps,
                'compensated_steps' => $sagaResult->compensatedSteps,
                'failed_step' => $sagaResult->failedStep,
                'error_message' => $sagaResult->errorMessage,
                'message' => $sagaResult->isSuccessful()
                    ? 'Onboarding compliance workflow completed successfully'
                    : 'Onboarding compliance workflow failed',
                'data' => $sagaResult->data,
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to initiate onboarding compliance', [
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'party_id' => $partyId,
                'status' => 'failed',
                'message' => 'Failed to initiate onboarding compliance workflow',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get the current status of an onboarding workflow.
     *
     * @param string $instanceId Saga instance identifier
     * @return array<string, mixed> Workflow status
     */
    public function getOnboardingStatus(string $instanceId): array
    {
        $this->logger->info('Getting onboarding status', ['instance_id' => $instanceId]);

        try {
            $state = $this->workflow->getState($instanceId);

            if ($state === null) {
                return [
                    'success' => false,
                    'message' => 'Onboarding workflow not found',
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
            $this->logger->error('Failed to get onboarding status', [
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
     * Compensate (rollback) an onboarding workflow.
     *
     * @param string $instanceId Saga instance identifier
     * @param string $reason Reason for compensation
     * @return array<string, mixed> Compensation result
     */
    public function compensateOnboarding(
        string $instanceId,
        string $reason
    ): array {
        $this->logger->info('Compensating onboarding', [
            'instance_id' => $instanceId,
            'reason' => $reason,
        ]);

        try {
            $sagaResult = $this->workflow->compensate($instanceId, $reason);

            return [
                'success' => $sagaResult->isSuccessful(),
                'instance_id' => $instanceId,
                'status' => $sagaResult->status->value,
                'compensated_steps' => $sagaResult->compensatedSteps,
                'message' => $sagaResult->isSuccessful()
                    ? 'Onboarding compensated successfully'
                    : 'Onboarding compensation failed',
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to compensate onboarding', [
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
     * Check if a party can proceed with onboarding.
     *
     * @param string $partyId Party identifier
     * @return array<string, mixed> Eligibility check result
     */
    public function checkEligibility(string $partyId): array
    {
        $this->logger->info('Checking onboarding eligibility', ['party_id' => $partyId]);

        $issues = [];

        // Check if already verified
        if ($this->kycAdapter->isVerified($partyId)) {
            $issues[] = 'Party already has completed KYC verification';
        }

        // Check if on sanctions list
        if ($this->sanctionsAdapter->hasMatches($partyId)) {
            $issues[] = 'Party has potential sanctions matches requiring review';
        }

        // Check AML risk
        if ($this->amlAdapter->isHighRisk($partyId)) {
            $issues[] = 'Party is classified as high AML risk';
        }

        return [
            'eligible' => count($issues) === 0,
            'party_id' => $partyId,
            'issues' => $issues,
            'checked_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Get KYC verification status for a party.
     *
     * @param string $partyId Party identifier
     * @return array<string, mixed> KYC status
     */
    public function getKycStatus(string $partyId): array
    {
        return [
            'party_id' => $partyId,
            'is_verified' => $this->kycAdapter->isVerified($partyId),
            'can_transact' => $this->kycAdapter->canTransact($partyId),
            'status' => $this->kycAdapter->getStatus($partyId),
            'risk_level' => $this->kycAdapter->getRiskLevel($partyId),
            'requires_edd' => $this->kycAdapter->requiresEnhancedDueDiligence($partyId),
            'verification_score' => $this->kycAdapter->getVerificationScore($partyId),
        ];
    }

    /**
     * Get AML screening status for a party.
     *
     * @param string $partyId Party identifier
     * @return array<string, mixed> AML status
     */
    public function getAmlStatus(string $partyId): array
    {
        $assessment = $this->amlAdapter->getCurrentAssessment($partyId);

        return [
            'party_id' => $partyId,
            'risk_level' => $this->amlAdapter->getRiskLevel($partyId),
            'is_high_risk' => $this->amlAdapter->isHighRisk($partyId),
            'requires_edd' => $this->amlAdapter->requiresEdd($partyId),
            'recommendations' => $this->amlAdapter->getRecommendations($partyId),
            'assessment' => $assessment,
        ];
    }

    /**
     * Get sanctions screening status for a party.
     *
     * @param string $partyId Party identifier
     * @return array<string, mixed> Sanctions status
     */
    public function getSanctionsStatus(string $partyId): array
    {
        $screeningResult = $this->sanctionsAdapter->getScreeningResult($partyId);

        return [
            'party_id' => $partyId,
            'has_matches' => $this->sanctionsAdapter->hasMatches($partyId),
            'is_pep' => $this->sanctionsAdapter->isPep($partyId),
            'screening_result' => $screeningResult,
        ];
    }
}
