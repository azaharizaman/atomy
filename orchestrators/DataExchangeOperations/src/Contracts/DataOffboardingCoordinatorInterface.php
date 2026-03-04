<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Contracts;

interface DataOffboardingCoordinatorInterface
{
    /**
     * Execute offboarding/export workflow and return task id.
     *
     * @param array<string, mixed> $query
     * @param string $format
     * @param string $destination
     * @param array<int, string> $recipients
     * @return string
     */
    public function offboard(array $query, string $format, string $destination, array $recipients = []): string;
}
