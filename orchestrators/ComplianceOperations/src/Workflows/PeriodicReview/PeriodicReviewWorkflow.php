<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\PeriodicReview;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ComplianceOperations\Contracts\WorkflowStorageInterface;
use Nexus\ComplianceOperations\Workflows\AbstractSaga;
use Nexus\ComplianceOperations\Workflows\PeriodicReview\Steps\ReverificationStep;
use Nexus\ComplianceOperations\Workflows\PeriodicReview\Steps\ReviewTriggerStep;
use Nexus\ComplianceOperations\Workflows\PeriodicReview\Steps\RiskReassessmentStep;
use Nexus\ComplianceOperations\Workflows\PeriodicReview\Steps\StatusUpdateStep;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * PeriodicReviewWorkflow - Saga for automated periodic compliance reviews.
 *
 * This workflow orchestrates the periodic re-verification of existing parties,
 * including:
 * - Review triggering based on schedule or risk profile
 * - Identity and document reverification
 * - Risk reassessment
 * - Status update
 *
 * States:
 * - INITIATED: Review started
 * - TRIGGERED: Review trigger processed
 * - REVERIFICATION: Reverification in progress
 * - RISK_ASSESSMENT: Risk reassessment in progress
 * - STATUS_UPDATE: Status update in progress
 * - COMPLETED: Review completed
 * - COMPENSATED: Workflow rolled back
 *
 * @see ARCHITECTURE.md for workflow patterns
 */
final readonly class PeriodicReviewWorkflow extends AbstractSaga
{
    /**
     * Workflow state constants.
     */
    public const STATE_INITIATED = 'INITIATED';
    public const STATE_TRIGGERED = 'TRIGGERED';
    public const STATE_REVERIFICATION = 'REVERIFICATION';
    public const STATE_RISK_ASSESSMENT = 'RISK_ASSESSMENT';
    public const STATE_STATUS_UPDATE = 'STATUS_UPDATE';
    public const STATE_COMPLETED = 'COMPLETED';
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
            new ReviewTriggerStep($logger),
            new ReverificationStep($logger),
            new RiskReassessmentStep($logger),
            new StatusUpdateStep($logger),
        ];
    }

    /**
     * Get the unique saga identifier.
     */
    public function getId(): string
    {
        return 'periodic_review';
    }

    /**
     * Get the saga name.
     */
    public function getName(): string
    {
        return 'Periodic Review Workflow';
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
            'current_risk_level' => 'low',
            'current_risk_score' => 0,
            'current_compliance_status' => 'active',
            'last_review_date' => null,
        ];
    }
}
