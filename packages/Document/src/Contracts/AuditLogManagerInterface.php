<?php

declare(strict_types=1);

namespace Nexus\Document\Contracts;

interface AuditLogManagerInterface
{
    /**
     * @param array<string, mixed> $properties
     */
    public function log(
        string $logName,
        string $description,
        string $subjectType,
        string $subjectId,
        string $causerType,
        string $causerId,
        array $properties = [],
        int $level = 1
    ): void;
}
