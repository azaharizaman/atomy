<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\Monitoring;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ComplianceOperations\Contracts\WorkflowStorageInterface;
use Nexus\ComplianceOperations\Workflows\AbstractSaga;
use Nexus\ComplianceOperations\Workflows\Monitoring\Steps\AlertGenerationStep;
use Nexus\ComplianceOperations\Workflows\Monitoring\Steps\EscalationHandlingStep;
use Nexus\ComplianceOperations\Workflows\Monitoring\Steps\RiskThresholdEvaluationStep;
use Nexus\ComplianceOperations\Workflows\Monitoring\Steps\TransactionScreeningStep;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * TransactionMonitoringWorkflow - Saga for real-time transaction monitoring.
 *
 * This workflow orchestrates the complete transaction monitoring process,
 * including:
 * - Transaction screening against AML patterns and sanctions
 * - Risk threshold evaluation
 * - Alert generation
 * - Escalation handling
 *
 * States:
 * - INITIATED: Monitoring started
 * - SCREENING: Transaction screening in progress
 * - THRESHOLD_EVALUATION: Risk threshold evaluation in progress
 * - ALERT_GENERATION: Alert generation in progress
 * - ESCALATION: Escalation handling in progress
 * - COMPLETED: Monitoring completed
 * - PENDING_REVIEW: Awaiting manual review
 * - COMPENSATED: Workflow rolled back
 *
 * @see ARCHITECTURE.md for workflow patterns
 */
final readonly class TransactionMonitoringWorkflow extends AbstractSaga
{
    /**
     * Workflow state constants.
     */
    public const STATE_INITIATED = 'INITIATED';
    public const STATE_SCREENING = 'SCREENING';
    public const STATE_THRESHOLD_EVALUATION = 'THRESHOLD_EVALUATION';
    public const STATE_ALERT_GENERATION = 'ALERT_GENERATION';
    public const STATE_ESCALATION = 'ESCALATION';
    public const STATE_COMPLETED = 'COMPLETED';
    public const STATE_PENDING_REVIEW = 'PENDING_REVIEW';
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
            new TransactionScreeningStep($logger),
            new RiskThresholdEvaluationStep($logger),
            new AlertGenerationStep($logger),
            new EscalationHandlingStep($logger),
        ];
    }

    /**
     * Get the unique saga identifier.
     */
    public function getId(): string
    {
        return 'transaction_monitoring';
    }

    /**
     * Get the saga name.
     */
    public function getName(): string
    {
        return 'Transaction Monitoring Workflow';
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
            'transaction_id',
            'transaction_amount',
            'transaction_currency',
            'party_id',
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
            'counterparty_id' => null,
            'counterparty_country' => 'US',
            'transaction_type' => 'payment',
            'source_of_funds' => null,
        ];
    }
}
