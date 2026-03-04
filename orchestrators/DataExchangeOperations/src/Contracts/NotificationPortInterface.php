<?php

declare(strict_types=1);

namespace Nexus\DataExchangeOperations\Contracts;

interface NotificationPortInterface
{
    /**
     * @param array<int, string> $recipients
     * @param array<string, mixed> $context
     */
    public function notify(array $recipients, string $template, array $context): void;
}
