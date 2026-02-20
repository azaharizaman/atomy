<?php

declare(strict_types=1);

namespace Nexus\Treasury\Services;

use DateTimeImmutable;
use Nexus\Common\ValueObjects\Money;
use Nexus\Treasury\Contracts\CashConcentrationInterface;
use Nexus\Treasury\Contracts\Integration\CashManagementProviderInterface;
use Nexus\Treasury\Contracts\LiquidityPoolQueryInterface;
use Nexus\Treasury\ValueObjects\CashSweepInstruction;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class CashConcentrationService
{
    public function __construct(
        private LiquidityPoolQueryInterface $liquidityPoolQuery,
        private ?CashManagementProviderInterface $cashManagementProvider = null,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function generateSweepInstructions(
        string $tenantId,
        string $targetAccountId,
        Money $threshold,
        array $sourceAccountIds,
        ?Money $retainAmount = null
    ): array {
        $instructions = [];

        foreach ($sourceAccountIds as $sourceAccountId) {
            $balance = $this->getAccountBalance($sourceAccountId);
            $currency = $this->getAccountCurrency($sourceAccountId);

            if ($currency !== $threshold->getCurrency()) {
                continue;
            }

            if ($balance <= $threshold->getAmount()) {
                continue;
            }

            $sweepAmount = $balance - $threshold->getAmount();

            if ($retainAmount !== null && $retainAmount->getAmount() > 0) {
                $sweepAmount = max(0, $sweepAmount - $retainAmount->getAmount());
            }

            if ($sweepAmount > 0) {
                $instructions[] = new CashSweepInstruction(
                    sourceAccountId: $sourceAccountId,
                    targetAccountId: $targetAccountId,
                    sweepThreshold: $threshold,
                    sweepAmount: Money::of($sweepAmount, $currency),
                    retainMinimum: $retainAmount !== null,
                    retainAmount: $retainAmount
                );
            }
        }

        $this->logger->info('Sweep instructions generated', [
            'tenant_id' => $tenantId,
            'target_account' => $targetAccountId,
            'instruction_count' => count($instructions),
        ]);

        return $instructions;
    }

    public function executeSweep(CashSweepInstruction $instruction): bool
    {
        $this->logger->info('Executing cash sweep', [
            'source' => $instruction->sourceAccountId,
            'target' => $instruction->targetAccountId,
            'amount' => $instruction->sweepAmount->format(),
        ]);

        if ($this->cashManagementProvider === null) {
            $this->logger->warning('Cash sweep execution skipped - no cash management provider configured');
            return false;
        }

        return true;
    }

    public function executeAllSweeps(array $instructions): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
            'total_swept' => 0.0,
        ];

        $currency = null;

        foreach ($instructions as $instruction) {
            if ($currency === null) {
                $currency = $instruction->getCurrency();
            } else {
                $instructionCurrency = $instruction->getCurrency();
                if ($instructionCurrency !== $currency) {
                    $this->logger->warning('Currency mismatch in batch sweep', [
                        'instruction_id' => $instruction->sourceAccountId,
                        'expected_currency' => $currency,
                        'actual_currency' => $instructionCurrency,
                    ]);
                    $results['failed'][] = $instruction;
                    continue;
                }
            }

            if ($this->executeSweep($instruction)) {
                $results['successful'][] = $instruction;
                $results['total_swept'] += $instruction->sweepAmount->getAmount();
            } else {
                $results['failed'][] = $instruction;
            }
        }

        $this->logger->info('Batch sweep execution completed', [
            'successful' => count($results['successful']),
            'failed' => count($results['failed']),
            'total_swept' => $results['total_swept'],
        ]);

        return $results;
    }

    public function calculateOptimalSweep(
        string $sourceAccountId,
        string $targetAccountId,
        Money $threshold,
        ?Money $minimumRetained = null
    ): ?CashSweepInstruction {
        $balance = $this->getAccountBalance($sourceAccountId);
        $currency = $this->getAccountCurrency($sourceAccountId);

        if ($currency !== $threshold->getCurrency()) {
            return null;
        }

        if ($balance <= $threshold->getAmount()) {
            return null;
        }

        $availableForSweep = $balance - $threshold->getAmount();

        if ($minimumRetained !== null && $minimumRetained->getAmount() > 0) {
            $retained = min($minimumRetained->getAmount(), $availableForSweep);
            $availableForSweep -= $retained;
        }

        if ($availableForSweep <= 0) {
            return null;
        }

        return new CashSweepInstruction(
            sourceAccountId: $sourceAccountId,
            targetAccountId: $targetAccountId,
            sweepThreshold: $threshold,
            sweepAmount: Money::of($availableForSweep, $currency),
            retainMinimum: $minimumRetained !== null,
            retainAmount: $minimumRetained
        );
    }

    public function getAccountsWithExcessCash(
        Money $threshold,
        array $accountIds
    ): array {
        $excessAccounts = [];

        foreach ($accountIds as $accountId) {
            $balance = $this->getAccountBalance($accountId);
            $currency = $this->getAccountCurrency($accountId);

            if ($currency !== $threshold->getCurrency()) {
                continue;
            }

            if ($balance > $threshold->getAmount()) {
                $excessAccounts[] = [
                    'account_id' => $accountId,
                    'balance' => $balance,
                    'excess' => $balance - $threshold->getAmount(),
                    'currency' => $currency,
                ];
            }
        }

        usort($excessAccounts, fn($a, $b) => $b['excess'] <=> $a['excess']);

        return $excessAccounts;
    }

    public function calculateConcentrationEfficiency(
        string $targetAccountId,
        array $sourceAccountIds
    ): array {
        $totalBalance = 0.0;
        $targetBalance = 0.0;
        $currency = null;

        foreach ($sourceAccountIds as $accountId) {
            $balance = $this->getAccountBalance($accountId);
            $accountCurrency = $this->getAccountCurrency($accountId);

            if ($currency === null) {
                $currency = $accountCurrency;
            }

            if ($accountCurrency === $currency) {
                $totalBalance += $balance;
            }
        }

        $targetBalance = $this->getAccountBalance($targetAccountId);

        $efficiency = $totalBalance > 0 ? ($targetBalance / $totalBalance) * 100 : 0;

        return [
            'total_balance' => $totalBalance,
            'target_balance' => $targetBalance,
            'concentration_percentage' => min($efficiency, 100),
            'unconcentrated' => max($totalBalance - $targetBalance, 0),
            'currency' => $currency ?? 'USD',
        ];
    }

    private function getAccountBalance(string $accountId): float
    {
        if ($this->cashManagementProvider === null) {
            return 0.0;
        }

        return $this->cashManagementProvider->getCurrentBalance($accountId);
    }

    private function getAccountCurrency(string $accountId): string
    {
        if ($this->cashManagementProvider === null) {
            return 'USD';
        }

        return $this->cashManagementProvider->getCurrency($accountId);
    }
}
