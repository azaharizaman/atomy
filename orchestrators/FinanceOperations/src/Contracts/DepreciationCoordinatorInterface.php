<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\DepreciationRunRequest;
use Nexus\FinanceOperations\DTOs\DepreciationRunResult;
use Nexus\FinanceOperations\DTOs\DepreciationScheduleRequest;
use Nexus\FinanceOperations\DTOs\DepreciationScheduleResult;
use Nexus\FinanceOperations\DTOs\RevaluationRequest;
use Nexus\FinanceOperations\DTOs\RevaluationResult;

/**
 * Contract for depreciation coordination operations.
 *
 * This coordinator handles fixed asset depreciation operations including
 * depreciation runs, schedule generation, and asset revaluation workflows.
 *
 * Interface Segregation Compliance:
 * - Extends FinanceCoordinatorInterface for base coordinator contract
 * - Defines only depreciation specific operations
 * - Uses DTOs for request/response to maintain type safety
 *
 * @see FinanceCoordinatorInterface
 */
interface DepreciationCoordinatorInterface extends FinanceCoordinatorInterface
{
    /**
     * Run depreciation calculation for a period.
     *
     * @param DepreciationRunRequest $request The depreciation run request
     * @return DepreciationRunResult The depreciation run result
     */
    public function runDepreciation(DepreciationRunRequest $request): DepreciationRunResult;

    /**
     * Generate depreciation schedules for assets.
     *
     * @param DepreciationScheduleRequest $request The schedule request
     * @return DepreciationScheduleResult The schedule result
     */
    public function generateSchedules(DepreciationScheduleRequest $request): DepreciationScheduleResult;

    /**
     * Process asset revaluation for fair value adjustments.
     *
     * @param RevaluationRequest $request The revaluation request
     * @return RevaluationResult The revaluation result
     */
    public function processRevaluation(RevaluationRequest $request): RevaluationResult;
}
