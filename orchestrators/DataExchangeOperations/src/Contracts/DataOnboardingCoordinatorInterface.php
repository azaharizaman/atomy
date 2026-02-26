<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Contracts;

/**
 * Interface DataOnboardingCoordinatorInterface
 *
 * Coordinates bulk data onboarding workflows including ingestion,
 * validation, storage, and automated cleanup.
 */
interface DataOnboardingCoordinatorInterface
{
    /**
     * Executes a bulk onboarding task.
     *
     * @param string $sourcePath Path to the source data file.
     * @param string $tenantId The target tenant identifier.
     * @param array $options Configuration options for the onboarding task.
     * @return string The task identifier.
     */
    public function onboard(string $sourcePath, string $tenantId, array $options = []): string;

    /**
     * Checks the status of an onboarding task.
     *
     * @param string $taskId
     * @return array Status information.
     */
    public function getTaskStatus(string $taskId): array;
}
