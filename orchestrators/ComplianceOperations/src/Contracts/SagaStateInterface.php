<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Contracts;

use Nexus\ComplianceOperations\Enums\SagaStatus;

/**
 * Defines contract for saga state persistence.
 */
interface SagaStateInterface
{
    /**
     * Get the saga instance ID.
     */
    public function getInstanceId(): string;

    /**
     * Get the saga type ID.
     */
    public function getSagaId(): string;

    /**
     * Get the tenant ID.
     */
    public function getTenantId(): string;

    /**
     * Get the current status.
     */
    public function getStatus(): SagaStatus;

    /**
     * Get the list of completed step IDs.
     *
     * @return array<string>
     */
    public function getCompletedSteps(): array;

    /**
     * Get the list of compensated step IDs.
     *
     * @return array<string>
     */
    public function getCompensatedSteps(): array;

    /**
     * Get the context data.
     *
     * @return array<string, mixed>
     */
    public function getContextData(): array;

    /**
     * Get the step output data.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getStepData(): array;

    /**
     * Get the error message (if any).
     */
    public function getErrorMessage(): ?string;

    /**
     * Get the created timestamp.
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get the last updated timestamp.
     */
    public function getUpdatedAt(): \DateTimeImmutable;

    /**
     * Check if the saga is in a terminal state.
     */
    public function isTerminal(): bool;
}
