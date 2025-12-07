<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\StepContext;
use Nexus\ProcurementOperations\DTOs\StepResult;

/**
 * Defines contract for individual workflow steps.
 *
 * Each step represents a discrete unit of work within a workflow.
 */
interface WorkflowStepInterface
{
    /**
     * Get the step identifier.
     */
    public function getId(): string;

    /**
     * Get the step name.
     */
    public function getName(): string;

    /**
     * Get the step description.
     */
    public function getDescription(): string;

    /**
     * Execute the step.
     *
     * @param StepContext $context Step execution context
     * @return StepResult Result of step execution
     */
    public function execute(StepContext $context): StepResult;

    /**
     * Check if step should be skipped based on context.
     *
     * @param StepContext $context Step context to evaluate
     * @return bool True if step should be skipped
     */
    public function shouldSkip(StepContext $context): bool;

    /**
     * Get the order/sequence of this step.
     */
    public function getOrder(): int;

    /**
     * Check if this step is required (cannot be skipped).
     */
    public function isRequired(): bool;

    /**
     * Get the timeout for this step in seconds.
     */
    public function getTimeout(): int;
}
