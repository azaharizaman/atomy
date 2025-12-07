<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use DateTimeImmutable;
use Nexus\ProcurementOperations\Enums\SagaStatus;

/**
 * Represents the current state of a saga instance.
 */
interface SagaStateInterface
{
    /**
     * Get the saga instance ID.
     */
    public function getInstanceId(): string;

    /**
     * Get the saga definition ID.
     */
    public function getSagaId(): string;

    /**
     * Get the current status.
     */
    public function getStatus(): SagaStatus;

    /**
     * Get the current step name/identifier.
     */
    public function getCurrentStep(): ?string;

    /**
     * Get the list of completed steps.
     *
     * @return array<string>
     */
    public function getCompletedSteps(): array;

    /**
     * Get the list of compensated steps.
     *
     * @return array<string>
     */
    public function getCompensatedSteps(): array;

    /**
     * Get the context data stored with the saga.
     *
     * @return array<string, mixed>
     */
    public function getContextData(): array;

    /**
     * Get step-specific data produced during execution.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getStepData(): array;

    /**
     * Get when the saga was started.
     */
    public function getStartedAt(): DateTimeImmutable;

    /**
     * Get when the saga was last updated.
     */
    public function getUpdatedAt(): DateTimeImmutable;

    /**
     * Get when the saga was completed (if applicable).
     */
    public function getCompletedAt(): ?DateTimeImmutable;

    /**
     * Get any error message if saga failed.
     */
    public function getErrorMessage(): ?string;

    /**
     * Get the step that caused failure (if any).
     */
    public function getFailedStep(): ?string;

    /**
     * Get the tenant ID this saga belongs to.
     */
    public function getTenantId(): string;

    /**
     * Check if saga is in a terminal state.
     */
    public function isTerminal(): bool;

    /**
     * Check if compensation was triggered.
     */
    public function wasCompensated(): bool;

    /**
     * Check if compensation completed successfully.
     */
    public function isCompensationComplete(): bool;
}
