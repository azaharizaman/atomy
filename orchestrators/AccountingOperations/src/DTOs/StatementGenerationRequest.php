<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\DTOs;

use Nexus\FinancialStatements\Enums\StatementType;
use Nexus\FinancialStatements\Enums\ComplianceFramework;

/**
 * Request DTO for statement generation operations.
 */
final readonly class StatementGenerationRequest
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $tenantId,
        public string $periodId,
        public StatementType $statementType,
        public ComplianceFramework $framework,
        public ?string $comparativePeriodId = null,
        public bool $includeNotes = true,
        public bool $includeAuditTrail = false,
        public array $options = [],
    ) {}
}
