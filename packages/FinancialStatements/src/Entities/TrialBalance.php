<?php

declare(strict_types=1);

namespace Nexus\FinancialStatements\Entities;

use Nexus\FinancialStatements\Contracts\FinancialStatementInterface;
use Nexus\FinancialStatements\ValueObjects\StatementMetadata;

/**
 * Trial Balance statement.
 */
final readonly class TrialBalance implements FinancialStatementInterface
{
    /**
     * @param StatementMetadata $metadata
     * @param array<string, mixed> $debits
     * @param array<string, mixed> $credits
     */
    public function __construct(
        private StatementMetadata $metadata,
        private array $debits,
        private array $credits,
    ) {}

    public function getMetadata(): StatementMetadata
    {
        return $this->metadata;
    }

    public function getSections(): array
    {
        return [
            'debits' => $this->debits,
            'credits' => $this->credits,
        ];
    }

    public function getDebits(): array
    {
        return $this->debits;
    }

    public function getCredits(): array
    {
        return $this->credits;
    }

    public function getTotalDebits(): float
    {
        return $this->calculateTotal($this->debits);
    }

    public function getTotalCredits(): float
    {
        return $this->calculateTotal($this->credits);
    }

    public function isValid(): bool
    {
        return abs($this->getTotalDebits() - $this->getTotalCredits()) < 0.01;
    }

    private function calculateTotal(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            if (is_array($item) && isset($item['amount'])) {
                $total += (float) $item['amount'];
            }
        }
        return $total;
    }
}
