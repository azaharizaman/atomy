<?php
declare(strict_types=1);

namespace Nexus\FinanceOperations\Coordinators;

use Nexus\FinanceOperations\Contracts\CashFlowCoordinatorInterface;
use Nexus\FinanceOperations\Contracts\TreasuryDataProviderInterface;
use Nexus\FinanceOperations\DTOs\CashPositionRequest;
use Nexus\FinanceOperations\DTOs\CashPositionResult;
use Nexus\FinanceOperations\DTOs\CashFlowForecastRequest;
use Nexus\FinanceOperations\DTOs\CashFlowForecastResult;
use Nexus\FinanceOperations\DTOs\BankReconciliationRequest;
use Nexus\FinanceOperations\DTOs\BankReconciliationResult;
use Nexus\FinanceOperations\Services\CashPositionService;
use Nexus\FinanceOperations\Exceptions\CashFlowException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for cash flow operations.
 * 
 * This coordinator manages the flow of cash-related operations:
 * - Cash position queries
 * - Cash flow forecasting
 * - Bank reconciliation
 * 
 * Following the Advanced Orchestrator Pattern:
 * - Coordinators direct flow, they do not execute business logic
 * - Delegates to services for calculations and heavy lifting
 * - Uses data providers for data aggregation
 * 
 * @see ARCHITECTURE.md Section 4: The Advanced Orchestrator Pattern
 * @since 1.0.0
 */
final readonly class CashFlowCoordinator implements CashFlowCoordinatorInterface
{
    public function __construct(
        private CashPositionService $cashPositionService,
        private TreasuryDataProviderInterface $treasuryDataProvider,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'CashFlowCoordinator';
    }

    /**
     * @inheritDoc
     */
    public function hasRequiredData(string $tenantId, string $periodId): bool
    {
        try {
            $accounts = $this->treasuryDataProvider->getBankAccounts($tenantId);
            return count($accounts) > 0;
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to check required data', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getSupportedOperations(): array
    {
        return [
            'get_cash_position',
            'generate_forecast',
            'reconcile_bank_account',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getCashPosition(CashPositionRequest $request): CashPositionResult
    {
        $this->logger->info('Coordinating cash position request', [
            'tenant_id' => $request->tenantId,
            'bank_account_id' => $request->bankAccountId,
        ]);

        try {
            // Convert to CashFlow namespace DTO for service
            $serviceRequest = new \Nexus\FinanceOperations\DTOs\CashFlow\CashPositionRequest(
                tenantId: $request->tenantId,
                bankAccountId: $request->bankAccountId,
                asOfDate: $request->asOfDate,
            );

            // Delegate to service
            $serviceResult = $this->cashPositionService->getPosition($serviceRequest);

            // Convert back to interface DTO
            $balance = "0";
            $balances = [];
            foreach ($serviceResult->positions as $position) {
                $balances[$position['bank_account_id']] = $position['balance'];
                $balance = bcadd($balance, (string)$position['balance'], 2);
            }

            $result = new CashPositionResult(
                success: $serviceResult->success,
                bankAccountId: $request->bankAccountId,
                balance: $balance,
                currency: $serviceResult->currency,
                asOfDate: $serviceResult->asOfDate,
                balances: $balances,
                errorMessage: $serviceResult->error,
            );

            // Dispatch event if dispatcher available
            $this->eventDispatcher?->dispatch(new class($request->tenantId, $result) {
                public function __construct(
                    public string $tenantId,
                    public CashPositionResult $result,
                ) {}
            });

            return $result;
        } catch (CashFlowException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Cash position coordination failed', [
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
     * @inheritDoc
     */
    public function generateForecast(CashFlowForecastRequest $request): CashFlowForecastResult
    {
        $this->logger->info('Coordinating cash flow forecast request', [
            'tenant_id' => $request->tenantId,
            'bank_account_id' => $request->bankAccountId,
        ]);

        try {
            // Convert to CashFlow namespace DTO for service
            // Note: The interface uses bankAccountId/startDate/daysAhead, service uses periodId/forecastDays
            $serviceRequest = new \Nexus\FinanceOperations\DTOs\CashFlow\CashFlowForecastRequest(
                tenantId: $request->tenantId,
                periodId: $request->options['period_id'] ?? '',
                forecastDays: $request->daysAhead,
            );

            // Delegate to service
            $serviceResult = $this->cashPositionService->generateForecast($serviceRequest);

            // Convert back to interface DTO
            $result = new CashFlowForecastResult(
                success: $serviceResult->success,
                tenantId: $serviceResult->tenantId,
                bankAccountId: $request->bankAccountId,
                forecast: $serviceResult->forecastData,
                generatedAt: new \DateTimeImmutable(),
                errorMessage: $serviceResult->error,
            );

            // Dispatch event
            $this->eventDispatcher?->dispatch(new class($request->tenantId, $result) {
                public function __construct(
                    public string $tenantId,
                    public CashFlowForecastResult $result,
                ) {}
            });

            return $result;
        } catch (CashFlowException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('Forecast coordination failed', [
                'tenant_id' => $request->tenantId,
                'error' => $e->getMessage(),
            ]);

            throw CashFlowException::forecastGenerationFailed(
                $request->tenantId,
                $request->options['period_id'] ?? 'unknown',
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function reconcileBankAccount(BankReconciliationRequest $request): BankReconciliationResult
    {
        $this->logger->info('Coordinating bank reconciliation request', [
            'tenant_id' => $request->tenantId,
            'bank_account_id' => $request->bankAccountId,
        ]);

        try {
            // Get reconciliation data from provider
            $reconData = $this->treasuryDataProvider->getBankReconciliationData(
                $request->tenantId,
                $request->bankAccountId
            );

            $matched = $reconData['matched_count'] ?? 0;
            $unmatched = $reconData['unmatched_count'] ?? 0;

            // Dispatch completion event
            $this->eventDispatcher?->dispatch(new class(
                $request->tenantId,
                $request->bankAccountId,
                $matched,
                $unmatched
            ) {
                public function __construct(
                    public string $tenantId,
                    public string $bankAccountId,
                    public int $matchedCount,
                    public int $unmatchedCount,
                ) {}
            });

            return new BankReconciliationResult(
                success: true,
                bankAccountId: $request->bankAccountId,
                bookBalance: (float)($reconData['book_balance'] ?? 0),
                bankBalance: (float)($reconData['bank_balance'] ?? 0),
                difference: (float)($reconData['difference'] ?? 0),
                matchedTransactions: $reconData['matched_transactions'] ?? [],
                unmatchedItems: $reconData['unmatched_items'] ?? [],
            );
        } catch (\Throwable $e) {
            $this->logger->error('Bank reconciliation coordination failed', [
                'tenant_id' => $request->tenantId,
                'bank_account_id' => $request->bankAccountId,
                'error' => $e->getMessage(),
            ]);

            throw CashFlowException::reconciliationFailed(
                $request->tenantId,
                $request->bankAccountId,
                0,
                $e->getMessage()
            );
        }
    }
}
