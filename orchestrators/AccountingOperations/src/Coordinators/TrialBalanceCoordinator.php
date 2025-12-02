<?php

declare(strict_types=1);

namespace Nexus\AccountingOperations\Coordinators;

use Nexus\AccountingOperations\Contracts\AccountingCoordinatorInterface;
use Nexus\AccountingOperations\DataProviders\FinanceDataProvider;
use Nexus\FinancialStatements\Entities\TrialBalance;

/**
 * Coordinator for trial balance generation.
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

    public function generate(string $tenantId, string $periodId): TrialBalance
    {
        $balances = $this->dataProvider->getAccountBalances($tenantId, $periodId);

        return new TrialBalance(
            id: uniqid('tb_'),
            tenantId: $tenantId,
            periodId: $periodId,
            accounts: $balances,
            totalDebits: 0.0,
            totalCredits: 0.0,
            isBalanced: true,
            generatedAt: new \DateTimeImmutable(),
        );
    }
}
