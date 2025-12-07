<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\CreateRequisitionRequest;
use Nexus\ProcurementOperations\DTOs\RequisitionResult;

/**
 * Contract for requisition workflow coordination.
 *
 * Handles the creation, validation, approval, and rejection of purchase requisitions.
 * Integrates with Budget for commitment tracking and Workflow for approvals.
 */
interface RequisitionCoordinatorInterface
{
    /**
     * Create a new purchase requisition.
     *
     * This operation:
     * 1. Validates requisition data
     * 2. Checks budget availability
     * 3. Creates budget reservation
     * 4. Creates requisition in Procurement package
     * 5. Initiates approval workflow
     * 6. Dispatches RequisitionCreatedEvent
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\RequisitionException
     * @throws \Nexus\ProcurementOperations\Exceptions\BudgetUnavailableException
     */
    public function create(CreateRequisitionRequest $request): RequisitionResult;

    /**
     * Approve a purchase requisition.
     *
     * This operation:
     * 1. Validates approver authorization
     * 2. Commits budget reservation
     * 3. Updates requisition status
     * 4. Dispatches RequisitionApprovedEvent
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\RequisitionException
     * @throws \Nexus\ProcurementOperations\Exceptions\UnauthorizedApprovalException
     */
    public function approve(
        string $tenantId,
        string $requisitionId,
        string $approvedBy,
        ?string $comments = null
    ): RequisitionResult;

    /**
     * Reject a purchase requisition.
     *
     * This operation:
     * 1. Releases budget reservation
     * 2. Updates requisition status
     * 3. Dispatches RequisitionRejectedEvent
     * 4. Notifies requester
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\RequisitionException
     */
    public function reject(
        string $tenantId,
        string $requisitionId,
        string $rejectedBy,
        string $reason
    ): RequisitionResult;

    /**
     * Cancel a requisition (before PO creation).
     *
     * @throws \Nexus\ProcurementOperations\Exceptions\RequisitionException
     */
    public function cancel(
        string $tenantId,
        string $requisitionId,
        string $cancelledBy,
        string $reason
    ): RequisitionResult;
}
