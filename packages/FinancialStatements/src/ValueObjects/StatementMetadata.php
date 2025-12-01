<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\ValueObjects;

use Nexus\FinancialStatements\Enums\StatementType;
use Nexus\FinancialStatements\Enums\ComplianceFramework;

/**
 * Metadata for a financial statement.
 */
final readonly class StatementMetadata
{
    public function __construct(
        private string $entityId,
        private string $entityName,
        private StatementType $statementType,
        private ComplianceFramework $complianceFramework,
        private \DateTimeImmutable $periodStart,
        private \DateTimeImmutable $periodEnd,
        private \DateTimeImmutable $generatedAt,
        private ?string $preparedBy = null,
        private ?string $approvedBy = null,
    ) {}

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function getStatementType(): StatementType
    {
        return $this->statementType;
    }

    public function getComplianceFramework(): ComplianceFramework
    {
        return $this->complianceFramework;
    }

    public function getPeriodStart(): \DateTimeImmutable
    {
        return $this->periodStart;
    }

    public function getPeriodEnd(): \DateTimeImmutable
    {
        return $this->periodEnd;
    }

    public function getGeneratedAt(): \DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function getPreparedBy(): ?string
    {
        return $this->preparedBy;
    }

    public function getApprovedBy(): ?string
    {
        return $this->approvedBy;
    }
}
