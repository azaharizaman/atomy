<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface NotificationManagerInterface
{
    /**
     * @param array<int, array<string, mixed>> $recipients
     * @param array<int, string> $channels
     * @param array<string, mixed> $context
     */
    public function send(
        array $recipients,
        string $subject,
        string $message,
        array $channels,
        string $priority,
        array $context = []
    ): void;
}
