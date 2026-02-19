<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Workflows\PrivacyRights;

use Nexus\ComplianceOperations\Contracts\SagaStepInterface;
use Nexus\ComplianceOperations\Contracts\SecureIdGeneratorInterface;
use Nexus\ComplianceOperations\Contracts\WorkflowStorageInterface;
use Nexus\ComplianceOperations\Workflows\AbstractSaga;
use Nexus\ComplianceOperations\Workflows\PrivacyRights\Steps\DataDiscoveryStep;
use Nexus\ComplianceOperations\Workflows\PrivacyRights\Steps\RequestValidationStep;
use Nexus\ComplianceOperations\Workflows\PrivacyRights\Steps\ResponseGenerationStep;
use Nexus\ComplianceOperations\Workflows\PrivacyRights\Steps\SubjectRightsProcessingStep;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * PrivacyRightsWorkflow - Saga for Data Subject Access Request (DSAR) fulfillment.
 *
 * This workflow orchestrates the complete DSAR fulfillment process,
 * including:
 * - Request validation
 * - Data discovery across systems
 * - Subject rights processing
 * - Response generation
 *
 * Supported request types:
 * - access: Right to access personal data
 * - erasure: Right to be forgotten
 * - rectification: Right to correct inaccurate data
 * - restriction: Right to restrict processing
 * - portability: Right to data portability
 * - objection: Right to object to processing
 *
 * States:
 * - INITIATED: Request received
 * - VALIDATING: Request validation in progress
 * - DISCOVERING: Data discovery in progress
 * - PROCESSING: Rights processing in progress
 * - RESPONDING: Response generation in progress
 * - COMPLETED: Request fulfilled
 * - COMPENSATED: Workflow rolled back
 *
 * @see ARCHITECTURE.md for workflow patterns
 */
final readonly class PrivacyRightsWorkflow extends AbstractSaga
{
    /**
     * Workflow state constants.
     */
    public const STATE_INITIATED = 'INITIATED';
    public const STATE_VALIDATING = 'VALIDATING';
    public const STATE_DISCOVERING = 'DISCOVERING';
    public const STATE_PROCESSING = 'PROCESSING';
    public const STATE_RESPONDING = 'RESPONDING';
    public const STATE_COMPLETED = 'COMPLETED';
    public const STATE_COMPENSATED = 'COMPENSATED';

    /**
     * Supported request types.
     */
    public const REQUEST_TYPE_ACCESS = 'access';
    public const REQUEST_TYPE_ERASURE = 'erasure';
    public const REQUEST_TYPE_RECTIFICATION = 'rectification';
    public const REQUEST_TYPE_RESTRICTION = 'restriction';
    public const REQUEST_TYPE_PORTABILITY = 'portability';
    public const REQUEST_TYPE_OBJECTION = 'objection';

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
            new RequestValidationStep($logger),
            new DataDiscoveryStep($logger),
            new SubjectRightsProcessingStep($logger),
            new ResponseGenerationStep($logger),
        ];
    }

    /**
     * Get the unique saga identifier.
     */
    public function getId(): string
    {
        return 'privacy_rights';
    }

    /**
     * Get the saga name.
     */
    public function getName(): string
    {
        return 'Privacy Rights Workflow';
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
            'request_id',
            'request_type',
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
            'subject_id' => null,
            'subject_email' => null,
            'jurisdiction' => 'EU',
            'corrections' => [],
            'objection_grounds' => null,
        ];
    }

    /**
     * Get supported request types.
     *
     * @return array<string>
     */
    public function getSupportedRequestTypes(): array
    {
        return [
            self::REQUEST_TYPE_ACCESS,
            self::REQUEST_TYPE_ERASURE,
            self::REQUEST_TYPE_RECTIFICATION,
            self::REQUEST_TYPE_RESTRICTION,
            self::REQUEST_TYPE_PORTABILITY,
            self::REQUEST_TYPE_OBJECTION,
        ];
    }
}
