<?php

declare(strict_types=1);

namespace Nexus\FinanceOperations\Contracts;

use Nexus\FinanceOperations\DTOs\GLPostingRequest;
use Nexus\FinanceOperations\DTOs\GLPostingResult;
use Nexus\FinanceOperations\DTOs\GLReconciliationRequest;
use Nexus\FinanceOperations\DTOs\GLReconciliationResult;
use Nexus\FinanceOperations\DTOs\ConsistencyCheckRequest;
use Nexus\FinanceOperations\DTOs\ConsistencyCheckResult;

/**
 * Contract for GL posting coordination operations.
 *
 * This coordinator handles general ledger posting operations including
 * subledger-to-GL posting, reconciliation, and consistency validation workflows.
 *
 * Interface Segregation Compliance:
 * - Extends FinanceCoordinatorInterface for base coordinator contract
 * - Defines only GL posting specific operations
 * - Uses DTOs for request/response to maintain type safety
 *
 * @see FinanceCoordinatorInterface
 */
interface GLPostingCoordinatorInterface extends FinanceCoordinatorInterface
{
    /**
     * Post subledger transactions to the general ledger.
     *
     * @param GLPostingRequest $request The posting request
     * @return GLPostingResult The posting result
     */
    public function postToGL(GLPostingRequest $request): GLPostingResult;

    /**
     * Reconcile subledger balances with GL balances.
     *
     * @param GLReconciliationRequest $request The reconciliation request
     * @return GLReconciliationResult The reconciliation result
     */
    public function reconcileWithGL(GLReconciliationRequest $request): GLReconciliationResult;

    /**
     * Validate posting consistency across subledgers and GL.
     *
     * @param ConsistencyCheckRequest $request The consistency check request
     * @return ConsistencyCheckResult The consistency check result
     */
    public function validateConsistency(ConsistencyCheckRequest $request): ConsistencyCheckResult;
}
