<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Contracts;

interface DataOnboardingCoordinatorInterface
{
    /**
     * Execute a bulk onboarding workflow and return task id.
     *
     * @param array<string, mixed> $options
     */
    public function onboard(string $sourcePath, string $tenantId, array $options = []): string;

    /**
     * @return array<string, mixed>
     */
    public function getTaskStatus(string $taskId): array;
}
