<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface AuditLoggerInterface
{
    /**
     * @param array<string, mixed>|string $context
     */
    public function log(string $entityId, string $action, array|string $context = []): void;
}
