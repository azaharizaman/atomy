<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

interface AuditLogManagerInterface
{
    /** @param array<string, mixed> $properties */
    public function log(string $logName, string $description, string $subjectType, string $subjectId, int $level = 1, array $properties = []): void;
}
