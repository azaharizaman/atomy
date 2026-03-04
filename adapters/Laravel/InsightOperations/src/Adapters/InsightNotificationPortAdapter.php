<?php

declare(strict_types=1);

namespace Nexus\Laravel\InsightOperations\Adapters;

use Nexus\InsightOperations\Contracts\InsightNotificationPortInterface;
use Psr\Log\LoggerInterface;

final readonly class InsightNotificationPortAdapter implements InsightNotificationPortInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public function notify(array $recipients, string $template, array $payload): void
    {
        $this->logger->info('Insight notification dispatched.', [
            'template' => $template,
            'recipient_count' => count($recipients),
            'payload_summary' => array_keys($payload),
        ]);
    }
}
