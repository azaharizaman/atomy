<?php

declare(strict_types=1);

namespace Nexus\Budget\Contracts;

interface AuditLoggerInterface
{
    public function log(string $entityId, string $action, string $details): void;
}
