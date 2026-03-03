<?php

declare(strict_types=1);

namespace Nexus\Laravel\DataExchangeOperations\Adapters;

use Nexus\DataExchangeOperations\Contracts\NotificationPortInterface;
use Psr\Log\LoggerInterface;

final readonly class NotificationPortAdapter implements NotificationPortInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public function notify(array $recipients, string $template, array $context): void
    {
        $this->logger->info('Data exchange notifications dispatched.', [
            'template' => $template,
            'recipient_count' => count($recipients),
            'recipients' => $recipients,
            'context' => $context,
        ]);
    }
}
