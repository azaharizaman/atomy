<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\ValueObjects;

use Nexus\FinancialStatements\Enums\ComplianceFramework;

/**
 * Represents a compliance standard for financial statements.
 */
final readonly class ComplianceStandard
{
    public function __construct(
        private ComplianceFramework $framework,
        private string $version,
        private \DateTimeImmutable $effectiveDate,
        private array $requiredDisclosures = [],
    ) {}

    public function getFramework(): ComplianceFramework
    {
        return $this->framework;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getEffectiveDate(): \DateTimeImmutable
    {
        return $this->effectiveDate;
    }

    public function getRequiredDisclosures(): array
    {
        return $this->requiredDisclosures;
    }
}
