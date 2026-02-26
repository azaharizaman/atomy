<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Contracts;

/**
 * Interface DataOffboardingCoordinatorInterface
 *
 * Coordinates multi-format data offboarding, storage, and notification.
 */
interface DataOffboardingCoordinatorInterface
{
    /**
     * Executes a data offboarding/export task.
     *
     * @param array $query Criteria for data extraction.
     * @param string $format Target format (e.g., 'csv', 'json', 'pdf').
     * @param string $destination S3 bucket or storage path.
     * @param array $recipients List of notification recipients.
     * @return string The task identifier.
     */
    public function offboard(array $query, string $format, string $destination, array $recipients = []): string;
}
