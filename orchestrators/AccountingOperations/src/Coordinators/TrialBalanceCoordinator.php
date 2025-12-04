<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Coordinators;

use Nexus\AccountingOperations\Contracts\AccountingCoordinatorInterface;
use Nexus\AccountingOperations\DataProviders\FinanceDataProvider;
use Nexus\AccountingOperations\DTOs\TrialBalanceDTO;

/**
 * Coordinator for trial balance generation.
 *
 * Orchestrates data from ChartOfAccount and JournalEntry packages
 * to generate trial balance reports.
 */
final readonly class TrialBalanceCoordinator implements AccountingCoordinatorInterface
{
    public function __construct(
        private FinanceDataProvider $dataProvider,
    ) {}

    public function getName(): string
    {
        return 'trial_balance';
    }

    public function hasRequiredData(string $tenantId, string $periodId): bool
    {
        $balances = $this->dataProvider->getAccountBalances($tenantId, $periodId);
        return !empty($balances);
    }

    /**
     * @return array<string>
     */
    public function getSupportedOperations(): array
    {
        return ['generate', 'validate', 'compare'];
    }

    /**
     * Generate trial balance for a period.
     */
    public function generate(string $tenantId, string $periodId): TrialBalanceDTO
    {
        $balances = $this->dataProvider->getAccountBalances($tenantId, $periodId);

        $totalDebits = '0.00';
        $totalCredits = '0.00';

        foreach ($balances as $balance) {
            $totalDebits = bcadd($totalDebits, $balance->debitBalance, 2);
            $totalCredits = bcadd($totalCredits, $balance->creditBalance, 2);
        }

        $isBalanced = bccomp($totalDebits, $totalCredits, 2) === 0;

        return new TrialBalanceDTO(
            id: uniqid('tb_'),
            tenantId: $tenantId,
            periodId: $periodId,
            accounts: $balances,
            totalDebits: $totalDebits,
            totalCredits: $totalCredits,
            isBalanced: $isBalanced,
            generatedAt: new \DateTimeImmutable(),
        );
    }
}
