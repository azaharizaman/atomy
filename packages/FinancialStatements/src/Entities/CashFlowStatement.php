<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Entities;

use Nexus\FinancialStatements\Contracts\FinancialStatementInterface;
use Nexus\FinancialStatements\Enums\CashFlowMethod;
use Nexus\FinancialStatements\ValueObjects\StatementMetadata;

/**
 * Cash Flow Statement.
 */
final readonly class CashFlowStatement implements FinancialStatementInterface
{
    /**
     * @param StatementMetadata $metadata
     * @param CashFlowMethod $method
     * @param array<string, mixed> $operatingActivities
     * @param array<string, mixed> $investingActivities
     * @param array<string, mixed> $financingActivities
     */
    public function __construct(
        private StatementMetadata $metadata,
        private CashFlowMethod $method,
        private array $operatingActivities,
        private array $investingActivities,
        private array $financingActivities,
    ) {}

    public function getMetadata(): StatementMetadata
    {
        return $this->metadata;
    }

    public function getMethod(): CashFlowMethod
    {
        return $this->method;
    }

    public function getSections(): array
    {
        return [
            'operating_activities' => $this->operatingActivities,
            'investing_activities' => $this->investingActivities,
            'financing_activities' => $this->financingActivities,
        ];
    }

    public function getOperatingActivities(): array
    {
        return $this->operatingActivities;
    }

    public function getInvestingActivities(): array
    {
        return $this->investingActivities;
    }

    public function getFinancingActivities(): array
    {
        return $this->financingActivities;
    }

    public function getNetCashFlow(): float
    {
        return $this->calculateSectionTotal($this->operatingActivities)
            + $this->calculateSectionTotal($this->investingActivities)
            + $this->calculateSectionTotal($this->financingActivities);
    }

    public function isValid(): bool
    {
        return true;
    }

    private function calculateSectionTotal(array $section): float
    {
        $total = 0.0;
        foreach ($section as $item) {
            if (is_array($item) && isset($item['amount'])) {
                $total += (float) $item['amount'];
            }
        }
        return $total;
    }
}
