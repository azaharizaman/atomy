<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\DTOs;

/**
 * Request DTO for year-end close operations.
 */
final readonly class YearEndCloseRequest
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $tenantId,
        public string $fiscalYearId,
        public string $closedBy,
        public string $retainedEarningsAccountId,
        public bool $generateStatements = true,
        public bool $carryForwardBalances = true,
        public bool $closeDividends = true,
        public array $options = [],
    ) {}
}
