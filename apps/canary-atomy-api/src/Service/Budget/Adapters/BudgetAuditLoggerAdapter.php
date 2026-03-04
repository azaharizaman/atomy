<?php

declare(strict_types=1);

namespace App\Service\Budget\Adapters;

use Nexus\Budget\Contracts\AuditLoggerInterface;
use Psr\Log\LoggerInterface;

final readonly class BudgetAuditLoggerAdapter implements AuditLoggerInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public function log(string $entityId, string $action, array|string $context = []): void
    {
        $payload = is_array($context) ? $context : ['details' => $context];
        $payload['entity_id'] = $entityId;
        $payload['action'] = $action;

        $this->logger->info('Budget audit log entry.', $payload);
    }
}
