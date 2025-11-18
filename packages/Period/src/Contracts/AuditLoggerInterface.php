<?php

declare(strict_types=1);

namespace Nexus\Period\Contracts;

/**
 * Audit Logger Interface
 * 
 * Contract for audit logging needed by Period package.
 * This should be implemented by Nexus\AuditLogger package.
 */
interface AuditLoggerInterface
{
    /**
     * Log an audit event for a period
     * 
     * @param string $entityId The period ID
     * @param string $eventType The type of event (e.g., 'period_closed')
     * @param string $description Human-readable description
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function log(string $entityId, string $eventType, string $description, array $metadata = []): void;
}
