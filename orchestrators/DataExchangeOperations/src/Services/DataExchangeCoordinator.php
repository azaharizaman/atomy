<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Services;

use Nexus\DataExchangeOperations\Coordinators\DataExchangeCoordinator as Coordinator;
use Nexus\DataExchangeOperations\Contracts\DataOffboardingCoordinatorInterface;
use Nexus\DataExchangeOperations\Contracts\DataOnboardingCoordinatorInterface;

/**
 * Compatibility facade retained for current integrations.
 */
final readonly class DataExchangeCoordinator implements DataOnboardingCoordinatorInterface, DataOffboardingCoordinatorInterface
{
    public function __construct(private Coordinator $coordinator) {}

    public function onboard(string $sourcePath, string $tenantId, array $options = []): string
    {
        return $this->coordinator->onboard($sourcePath, $tenantId, $options);
    }

    public function getTaskStatus(string $taskId): array
    {
        return $this->coordinator->getTaskStatus($taskId);
    }

    public function offboard(array $query, string $format, string $destination, array $recipients = []): string
    {
        return $this->coordinator->offboard($query, $format, $destination, $recipients);
    }
}
