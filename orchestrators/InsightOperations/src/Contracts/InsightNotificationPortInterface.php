<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\Contracts;

interface InsightNotificationPortInterface
{
    /**
     * @param array<int, string> $recipients
     * @param array<string, mixed> $payload
     */
    public function notify(array $recipients, string $template, array $payload): void;
}
