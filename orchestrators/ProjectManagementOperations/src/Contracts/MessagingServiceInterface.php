<?php

declare(strict_types=1);

namespace Nexus\ProjectManagementOperations\Contracts;

interface MessagingServiceInterface
{
    /**
     * Send a notification to a recipient
     */
    public function sendNotification(
        string $tenantId,
        string $recipientId,
        string $template,
        array $data
    ): void;
}
