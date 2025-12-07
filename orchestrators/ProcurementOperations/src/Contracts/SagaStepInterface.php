<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\SagaStepContext;
use Nexus\ProcurementOperations\DTOs\SagaStepResult;

/**
 * Defines contract for individual saga steps with compensation.
 *
 * Each saga step has an execute action and a compensate action.
 * If any step fails, compensation is executed in reverse order
 * for all previously completed steps.
 */
interface SagaStepInterface
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
     * Execute the forward action.
     *
     * @param SagaStepContext $context Step execution context
     * @return SagaStepResult Result of step execution
     */
    public function execute(SagaStepContext $context): SagaStepResult;

    /**
     * Execute the compensation (rollback) action.
     *
     * Called when a later step fails to undo this step's effects.
     *
     * @param SagaStepContext $context Step compensation context
     * @return SagaStepResult Result of compensation
     */
    public function compensate(SagaStepContext $context): SagaStepResult;

    /**
     * Check if this step has compensation logic.
     */
    public function hasCompensation(): bool;

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

    /**
     * Get the number of retry attempts for this step.
     */
    public function getRetryAttempts(): int;
}
