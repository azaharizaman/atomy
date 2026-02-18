<?php

declare(strict_types=1);

namespace Nexus\SupplyChainOperations\Contracts;

interface AuditLoggerInterface
{
    public function log(string $logName, string $description, array $context = []): void;
}
