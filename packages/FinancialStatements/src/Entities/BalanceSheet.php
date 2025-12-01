<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Entities;

use Nexus\FinancialStatements\Contracts\FinancialStatementInterface;
use Nexus\FinancialStatements\ValueObjects\StatementMetadata;

/**
 * Balance Sheet (Statement of Financial Position).
 */
final readonly class BalanceSheet implements FinancialStatementInterface
{
    /**
     * @param StatementMetadata $metadata
     * @param array<string, mixed> $assets
     * @param array<string, mixed> $liabilities
     * @param array<string, mixed> $equity
     */
    public function __construct(
        private StatementMetadata $metadata,
        private array $assets,
        private array $liabilities,
        private array $equity,
    ) {}

    public function getMetadata(): StatementMetadata
    {
        return $this->metadata;
    }

    public function getSections(): array
    {
        return [
            'assets' => $this->assets,
            'liabilities' => $this->liabilities,
            'equity' => $this->equity,
        ];
    }

    public function getAssets(): array
    {
        return $this->assets;
    }

    public function getLiabilities(): array
    {
        return $this->liabilities;
    }

    public function getEquity(): array
    {
        return $this->equity;
    }

    public function isValid(): bool
    {
        $totalAssets = $this->calculateSectionTotal($this->assets);
        $totalLiabilitiesAndEquity = $this->calculateSectionTotal($this->liabilities)
            + $this->calculateSectionTotal($this->equity);

        return abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01;
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
