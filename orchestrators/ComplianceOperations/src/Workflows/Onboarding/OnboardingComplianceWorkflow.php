<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\Onboarding;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ComplianceOperations\Contracts\WorkflowStorageInterface;
use Nexus\ComplianceOperations\Workflows\AbstractSaga;
use Nexus\ComplianceOperations\Workflows\Onboarding\Steps\AmlScreeningStep;
use Nexus\ComplianceOperations\Workflows\Onboarding\Steps\ComplianceApprovalStep;
use Nexus\ComplianceOperations\Workflows\Onboarding\Steps\KycVerificationStep;
use Nexus\ComplianceOperations\Workflows\Onboarding\Steps\RiskAssessmentStep;
use Nexus\ComplianceOperations\Workflows\Onboarding\Steps\SanctionsCheckStep;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * OnboardingComplianceWorkflow - Saga for party onboarding compliance.
 *
 * This workflow orchestrates the complete compliance onboarding process
 * for new parties (customers, vendors, or employees), including:
 * - KYC verification
 * - AML screening
 * - Sanctions checking
 * - Risk assessment
 * - Compliance approval
 *
 * States:
 * - INITIATED: Onboarding started
 * - KYC_IN_PROGRESS: Identity verification in progress
 * - SCREENING_IN_PROGRESS: AML/Sanctions screening in progress
 * - RISK_ASSESSMENT: Risk evaluation in progress
 * - PENDING_APPROVAL: Awaiting compliance approval
 * - APPROVED: Onboarding approved
 * - REJECTED: Onboarding rejected
 * - COMPENSATED: Workflow rolled back
 *
 * @see ARCHITECTURE.md for workflow patterns
 */
final readonly class OnboardingComplianceWorkflow extends AbstractSaga
{
    /**
     * Workflow state constants.
     */
    public const STATE_INITIATED = 'INITIATED';
    public const STATE_KYC_IN_PROGRESS = 'KYC_IN_PROGRESS';
    public const STATE_SCREENING_IN_PROGRESS = 'SCREENING_IN_PROGRESS';
    public const STATE_RISK_ASSESSMENT = 'RISK_ASSESSMENT';
    public const STATE_PENDING_APPROVAL = 'PENDING_APPROVAL';
    public const STATE_APPROVED = 'APPROVED';
    public const STATE_REJECTED = 'REJECTED';
    public const STATE_COMPENSATED = 'COMPENSATED';

    /**
     * @var array<SagaStepInterface>
     */
    private array $steps;

    public function __construct(
        WorkflowStorageInterface $storage,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null,
        ?SecureIdGeneratorInterface $idGenerator = null,
    ) {
        parent::__construct($storage, $eventDispatcher, $logger, $idGenerator);

        // Initialize workflow steps in execution order
        $this->steps = [
            new KycVerificationStep($logger),
            new AmlScreeningStep($logger),
            new SanctionsCheckStep($logger),
            new RiskAssessmentStep($logger),
            new ComplianceApprovalStep($logger),
        ];
    }

    /**
     * Get the unique saga identifier.
     */
    public function getId(): string
    {
        return 'onboarding_compliance';
    }

    /**
     * Get the saga name.
     */
    public function getName(): string
    {
        return 'Onboarding Compliance Workflow';
    }

    /**
     * Get all saga steps.
     *
     * @return array<SagaStepInterface>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Get a step by its ID.
     */
    public function getStep(string $stepId): ?SagaStepInterface
    {
        foreach ($this->steps as $step) {
            if ($step->getId() === $stepId) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Get the total number of steps.
     */
    public function getTotalSteps(): int
    {
        return count($this->steps);
    }

    /**
     * Get required context fields for this workflow.
     *
     * @return array<string>
     */
    public function getRequiredContextFields(): array
    {
        return [
            'party_id',
            'party_type',
            'party_name',
            'country_code',
        ];
    }

    /**
     * Get optional context fields with defaults.
     *
     * @return array<string, mixed>
     */
    public function getOptionalContextFields(): array
    {
        return [
            'document_references' => [],
            'biometric_required' => true,
            'manual_approval' => null,
        ];
    }
}
