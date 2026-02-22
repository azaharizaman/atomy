<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Services;

use Nexus\FinanceOperations\Contracts\TreasuryDataProviderInterface;
use Nexus\FinanceOperations\DTOs\CashFlow\CashPositionRequest;
use Nexus\FinanceOperations\DTOs\CashFlow\CashPositionResult;
use Nexus\FinanceOperations\DTOs\CashFlow\CashFlowForecastRequest;
use Nexus\FinanceOperations\DTOs\CashFlow\CashFlowForecastResult;
use Nexus\FinanceOperations\Exceptions\CashFlowException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for cash position calculations and forecasting.
 * 
 * This service handles:
 * - Cash position aggregation across accounts
 * - Multi-currency consolidation
 * - Cash flow forecast calculations
 * 
 * Following Advanced Orchestrator Pattern v1.1:
 * Services handle the "heavy lifting" - calculations and cross-boundary logic.
 * 
 * @since 1.0.0
 */
final readonly class CashPositionService
{
    public function __construct(
        private TreasuryDataProviderInterface $dataProvider,
        private ?object $currencyConverter = null,  // CurrencyConverterInterface
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Get cash position for one or all bank accounts.
     *
     * @param CashPositionRequest $request The position request parameters
     * @return CashPositionResult The calculated cash position
     * @throws CashFlowException If position retrieval fails
     */
    public function getPosition(CashPositionRequest $request): CashPositionResult
    {
        $this->logger->info('Calculating cash position', [
            'tenant_id' => $request->tenantId,
            'bank_account_id' => $request->bankAccountId,
        ]);

        try {
            $positions = $this->fetchPositions($request);

            if (empty($positions)) {
                return new CashPositionResult(
                    success: true,
                    tenantId: $request->tenantId,
                    positions: [],
                    consolidatedBalance: '0',
                    currency: $request->currency,
                    asOfDate: $request->asOfDate ?? new \DateTimeImmutable(),
                );
            }

            // Consolidate positions (handle multi-currency if needed)
            $consolidatedBalance = $this->consolidateBalances(
                $positions,
                $request->currency
            );

            return new CashPositionResult(
                success: true,
                tenantId: $request->tenantId,
                positions: $positions,
                consolidatedBalance: $consolidatedBalance,
                currency: $request->currency,
                asOfDate: $request->asOfDate ?? new \DateTimeImmutable(),
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to calculate cash position', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            throw CashFlowException::cashPositionRetrievalFailed(
                $request->tenantId,
                $request->bankAccountId ?? 'all',
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Get consolidated cash position across all accounts.
     *
     * @param string $tenantId The tenant identifier
     * @param string $targetCurrency Target currency for consolidation (default: USD)
     * @return array<string, mixed> Consolidated position data
     * @throws CashFlowException If consolidation fails
     */
    public function getConsolidatedPosition(string $tenantId, string $targetCurrency = 'USD'): array
    {
        $this->logger->info('Calculating consolidated cash position', [
            'tenant_id' => $tenantId,
            'target_currency' => $targetCurrency,
        ]);

        try {
            // Get all bank accounts
            $bankAccounts = $this->dataProvider->getBankAccounts($tenantId);
            $positions = [];

            foreach ($bankAccounts as $account) {
                $accountId = $account['id'] ?? $account['bank_account_id'] ?? null;
                if ($accountId === null) {
                    continue;
                }

                $position = $this->dataProvider->getCashPosition($tenantId, $accountId);
                $positions[] = $position;
            }

            return [
                'positions' => $positions,
                'consolidated_balance' => $this->consolidateBalances($positions, $targetCurrency),
                'currency' => $targetCurrency,
                'account_count' => count($positions),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to get consolidated position', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw CashFlowException::cashPositionRetrievalFailed(
                $tenantId,
                'all',
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Generate cash flow forecast.
     *
     * @param CashFlowForecastRequest $request The forecast request parameters
     * @return CashFlowForecastResult The generated forecast
     * @throws CashFlowException If forecast generation fails
     */
    public function generateForecast(CashFlowForecastRequest $request): CashFlowForecastResult
    {
        $this->logger->info('Generating cash flow forecast', [
            'tenant_id' => $request->tenantId,
            'period_id' => $request->periodId,
            'forecast_days' => $request->forecastDays,
        ]);

        try {
            $forecastData = $this->dataProvider->getCashFlowForecast(
                $request->tenantId,
                $request->periodId
            );

            $inflows = $forecastData['inflows'] ?? [];
            $outflows = $forecastData['outflows'] ?? [];

            $totalInflows = $this->sumAmounts($inflows);
            $totalOutflows = $this->sumAmounts($outflows);
            $netCashFlow = (string)((float)$totalInflows - (float)$totalOutflows);

            return new CashFlowForecastResult(
                success: true,
                tenantId: $request->tenantId,
                forecastData: $forecastData['daily_forecast'] ?? [],
                inflows: $inflows,
                outflows: $outflows,
                netCashFlow: $netCashFlow,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to generate cash flow forecast', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            throw CashFlowException::forecastGenerationFailed(
                $request->tenantId,
                $request->periodId,
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Fetch positions based on request parameters.
     *
     * @param CashPositionRequest $request The position request
     * @return array<int, array<string, mixed>> List of position data
     */
    private function fetchPositions(CashPositionRequest $request): array
    {
        if ($request->bankAccountId !== null) {
            // Single account
            $position = $this->dataProvider->getCashPosition(
                $request->tenantId,
                $request->bankAccountId
            );
            return [$position];
        }

        // All accounts
        $bankAccounts = $this->dataProvider->getBankAccounts($request->tenantId);
        $positions = [];

        foreach ($bankAccounts as $account) {
            $accountId = $account['id'] ?? $account['bank_account_id'] ?? null;
            if ($accountId === null) {
                continue;
            }

            $positions[] = $this->dataProvider->getCashPosition(
                $request->tenantId,
                $accountId
            );
        }

        return $positions;
    }

    /**
     * Consolidate balances from multiple accounts/currencies.
     *
     * @param array<int, array<string, mixed>> $positions List of position data
     * @param string $targetCurrency Target currency for consolidation
     * @return string Consolidated balance as string
     */
    private function consolidateBalances(array $positions, string $targetCurrency): string
    {
        $total = 0.0;

        foreach ($positions as $position) {
            $balance = (float)($position['balance'] ?? $position['available_balance'] ?? 0);
            $currency = $position['currency'] ?? $targetCurrency;

            // Convert currency if needed
            if ($currency !== $targetCurrency && $this->currencyConverter !== null) {
                $balance = (float)$this->currencyConverter->convert(
                    (string)$balance,
                    $currency,
                    $targetCurrency
                );
            }

            $total += $balance;
        }

        return (string)round($total, 2);
    }

    /**
     * Sum amounts from an array of transactions.
     *
     * @param array<int, array<string, mixed>> $transactions List of transactions
     * @return string Total amount as string
     */
    private function sumAmounts(array $transactions): string
    {
        $total = 0.0;
        foreach ($transactions as $tx) {
            $total += (float)($tx['amount'] ?? 0);
        }
        return (string)round($total, 2);
    }
}
