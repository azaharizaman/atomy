<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Services;

/**
 * Interface for audit logging.
 */
interface AuditLoggerInterface
{
    public function log(string $event, string $entityId, array $data = []): void;
}
