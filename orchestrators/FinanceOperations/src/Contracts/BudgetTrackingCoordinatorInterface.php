<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\BudgetCheckRequest;
use Nexus\FinanceOperations\DTOs\BudgetCheckResult;
use Nexus\FinanceOperations\DTOs\BudgetVarianceRequest;
use Nexus\FinanceOperations\DTOs\BudgetVarianceResult;
use Nexus\FinanceOperations\DTOs\BudgetThresholdRequest;
use Nexus\FinanceOperations\DTOs\BudgetThresholdResult;

/**
 * Contract for budget tracking coordination operations.
 *
 * This coordinator handles budget monitoring operations including
 * availability checks, variance analysis, and threshold alerting workflows.
 *
 * Interface Segregation Compliance:
 * - Extends FinanceCoordinatorInterface for base coordinator contract
 * - Defines only budget tracking specific operations
 * - Uses DTOs for request/response to maintain type safety
 *
 * @see FinanceCoordinatorInterface
 */
interface BudgetTrackingCoordinatorInterface extends FinanceCoordinatorInterface
{
    /**
     * Check budget availability before committing funds.
     *
     * @param BudgetCheckRequest $request The budget check request
     * @return BudgetCheckResult The budget check result
     */
    public function checkBudgetAvailable(BudgetCheckRequest $request): BudgetCheckResult;

    /**
     * Calculate budget variances for a period.
     *
     * @param BudgetVarianceRequest $request The variance request
     * @return BudgetVarianceResult The variance result
     */
    public function calculateVariances(BudgetVarianceRequest $request): BudgetVarianceResult;

    /**
     * Check budget thresholds and generate alerts.
     *
     * @param BudgetThresholdRequest $request The threshold check request
     * @return BudgetThresholdResult The threshold check result
     */
    public function checkThresholds(BudgetThresholdRequest $request): BudgetThresholdResult;
}
