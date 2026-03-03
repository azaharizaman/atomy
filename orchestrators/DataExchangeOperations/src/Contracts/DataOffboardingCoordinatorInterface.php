<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Contracts;

interface DataOffboardingCoordinatorInterface
{
    /**
     * Execute offboarding/export workflow and return task id.
     *
     * @param array<string, mixed> $query
     * @param array<int, string> $recipients
     */
    public function offboard(array $query, string $format, string $destination, array $recipients = []): string;
}
