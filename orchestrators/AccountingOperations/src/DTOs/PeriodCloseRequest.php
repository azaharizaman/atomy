<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\DTOs;

/**
 * Request DTO for period close operations.
 */
final readonly class PeriodCloseRequest
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public string $closedBy,
        public bool $generateClosingEntries = true,
        public bool $generateStatements = true,
        public bool $lockPeriod = true,
        public array $options = [],
    ) {}
}
