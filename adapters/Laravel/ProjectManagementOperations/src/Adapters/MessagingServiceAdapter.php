<?php

declare(strict_types=1);

namespace Nexus\Laravel\ProjectManagementOperations\Adapters;

use Nexus\ProjectManagementOperations\Contracts\MessagingServiceInterface;
use Nexus\Laravel\ProjectManagementOperations\Contracts\ProjectMessagingSenderInterface;

/**
 * Implements orchestrator MessagingServiceInterface using app-provided sender (e.g. Notifier).
 */
final readonly class MessagingServiceAdapter implements MessagingServiceInterface
{
    public function __construct(
        private ProjectMessagingSenderInterface $sender,
    ) {
    }

    public function sendNotification(
        string $tenantId,
        string $recipientId,
        string $template,
        array $data
    ): void {
        $this->sender->send($tenantId, $recipientId, $template, $data);
    }
}
