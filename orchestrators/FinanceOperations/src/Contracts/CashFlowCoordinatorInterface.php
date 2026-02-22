<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\CashPositionRequest;
use Nexus\FinanceOperations\DTOs\CashPositionResult;
use Nexus\FinanceOperations\DTOs\CashFlowForecastRequest;
use Nexus\FinanceOperations\DTOs\CashFlowForecastResult;
use Nexus\FinanceOperations\DTOs\BankReconciliationRequest;
use Nexus\FinanceOperations\DTOs\BankReconciliationResult;

/**
 * Contract for cash flow coordination operations.
 *
 * This coordinator handles treasury operations including cash position
 * tracking, cash flow forecasting, and bank reconciliation workflows.
 *
 * Interface Segregation Compliance:
 * - Extends FinanceCoordinatorInterface for base coordinator contract
 * - Defines only cash flow specific operations
 * - Uses DTOs for request/response to maintain type safety
 *
 * @see FinanceCoordinatorInterface
 */
interface CashFlowCoordinatorInterface extends FinanceCoordinatorInterface
{
    /**
     * Get current cash position for a bank account.
     *
     * @param CashPositionRequest $request The cash position request
     * @return CashPositionResult The cash position result
     */
    public function getCashPosition(CashPositionRequest $request): CashPositionResult;

    /**
     * Generate cash flow forecast for a period.
     *
     * @param CashFlowForecastRequest $request The forecast request
     * @return CashFlowForecastResult The forecast result
     */
    public function generateForecast(CashFlowForecastRequest $request): CashFlowForecastResult;

    /**
     * Reconcile bank statements with internal records.
     *
     * @param BankReconciliationRequest $request The reconciliation request
     * @return BankReconciliationResult The reconciliation result
     */
    public function reconcileBankAccount(BankReconciliationRequest $request): BankReconciliationResult;
}
