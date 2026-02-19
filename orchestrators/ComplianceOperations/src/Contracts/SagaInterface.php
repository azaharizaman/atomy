<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Contracts;

use Nexus\ComplianceOperations\DTOs\SagaContext;
use Nexus\ComplianceOperations\DTOs\SagaResult;

/**
 * Defines contract for Saga pattern implementation.
 *
 * Sagas coordinate distributed transactions across multiple
 * services/packages with compensation logic for failures.
 * Each step has a corresponding compensation action that
 * undoes the step's effects if later steps fail.
 */
interface SagaInterface
{
    /**
     * Get the unique saga identifier.
     */
    public function getId(): string;

    /**
     * Get the saga name.
     */
    public function getName(): string;

    /**
     * Execute the saga with given context.
     *
     * Runs all steps in sequence. If any step fails,
     * compensation actions are executed in reverse order
     * for all completed steps.
     *
     * @param SagaContext $context Saga execution context
     * @return SagaResult Result containing success/failure and compensation status
     */
    public function execute(SagaContext $context): SagaResult;

    /**
     * Manually trigger compensation for a saga instance.
     *
     * @param string $sagaInstanceId The saga instance to compensate
     * @param string|null $reason Reason for compensation
     * @return SagaResult Result after compensation
     */
    public function compensate(string $sagaInstanceId, ?string $reason = null): SagaResult;

    /**
     * Get the current status of a saga instance.
     *
     * @param string $sagaInstanceId The saga instance to query
     * @return SagaStateInterface|null Current state or null if not found
     */
    public function getState(string $sagaInstanceId): ?SagaStateInterface;

    /**
     * Get all saga steps.
     *
     * @return array<SagaStepInterface>
     */
    public function getSteps(): array;

    /**
     * Check if saga can be executed with given context.
     *
     * @param SagaContext $context Context to validate
     * @return bool True if saga can execute
     */
    public function canExecute(SagaContext $context): bool;
}
