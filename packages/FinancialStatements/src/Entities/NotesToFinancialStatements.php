<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Entities;

use Nexus\FinancialStatements\Contracts\FinancialStatementInterface;
use Nexus\FinancialStatements\ValueObjects\StatementMetadata;

/**
 * Notes to Financial Statements (Required disclosures).
 */
final readonly class NotesToFinancialStatements implements FinancialStatementInterface
{
    /**
     * @param StatementMetadata $metadata
     * @param array<string, mixed> $accountingPolicies
     * @param array<string, mixed> $significantJudgments
     * @param array<string, mixed> $additionalDisclosures
     */
    public function __construct(
        private StatementMetadata $metadata,
        private array $accountingPolicies,
        private array $significantJudgments,
        private array $additionalDisclosures = [],
    ) {}

    public function getMetadata(): StatementMetadata
    {
        return $this->metadata;
    }

    public function getSections(): array
    {
        return [
            'accounting_policies' => $this->accountingPolicies,
            'significant_judgments' => $this->significantJudgments,
            'additional_disclosures' => $this->additionalDisclosures,
        ];
    }

    public function getAccountingPolicies(): array
    {
        return $this->accountingPolicies;
    }

    public function getSignificantJudgments(): array
    {
        return $this->significantJudgments;
    }

    public function getAdditionalDisclosures(): array
    {
        return $this->additionalDisclosures;
    }

    public function isValid(): bool
    {
        return !empty($this->accountingPolicies);
    }
}
