<?php

declare(strict_types=1);

namespace App\Services;

use Nexus\AuditLogger\Services\AuditLogManager;
use Nexus\Period\Contracts\AuditLoggerInterface;

/**
 * Period Audit Logger Adapter
 * 
 * Adapts Nexus\AuditLogger to the Period package's AuditLoggerInterface.
 */
final class PeriodAuditLoggerAdapter implements AuditLoggerInterface
{
    public function __construct(
        private readonly AuditLogManager $auditLogger
    ) {}

    /**
     * {@inheritDoc}
     */
    public function log(string $entityId, string $eventType, string $description, array $metadata = []): void
    {
        $this->auditLogger->log(
            entityType: 'period',
            entityId: $entityId,
            action: $eventType,
            description: $description,
            metadata: $metadata
        );
    }
}
