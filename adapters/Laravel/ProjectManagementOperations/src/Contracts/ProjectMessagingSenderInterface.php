<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Contracts;

/**
 * Sends a notification (template + data). Implemented by the app using Nexus\Notifier or similar.
 */
interface ProjectMessagingSenderInterface
{
    public function send(
        string $tenantId,
        string $recipientId,
        string $template,
        array $data
    ): void;
}
