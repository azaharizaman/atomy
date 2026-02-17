<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\ProcurementOperations\Contracts\RequisitionCoordinatorInterface;
use Nexus\ProcurementOperations\DTOs\CreateRequisitionRequest;
use Nexus\ProcurementOperations\DTOs\RequisitionResult;
use Nexus\ProcurementOperations\Exceptions\RequisitionException;
use Nexus\ProcurementOperations\Workflows\RequisitionApprovalWorkflow;
use Nexus\Procurement\Contracts\RequisitionManagerInterface;
use Nexus\Procurement\Contracts\RequisitionQueryInterface;
use Nexus\Budget\Contracts\BudgetManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates the requisition lifecycle from creation to approval/cancellation.
 */
final readonly class RequisitionCoordinator implements RequisitionCoordinatorInterface
{
    public function __construct(
        private RequisitionManagerInterface $requisitionManager,
        private RequisitionQueryInterface $requisitionQuery,
        private RequisitionApprovalWorkflow $approvalWorkflow,
        private ?BudgetManagerInterface $budgetManager = null,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @inheritDoc
     */
    public function create(CreateRequisitionRequest $request): RequisitionResult
    {
        $this->logger->info('Creating requisition via coordinator', [
            'tenant_id' => $request->tenantId,
            'requested_by' => $request->requestedBy,
        ]);

        try {
            // 1. Requisition creation in atomic package
            $requisition = $this->requisitionManager->create(
                tenantId: $request->tenantId,
                requesterId: $request->requestedBy,
                items: $request->lineItems,
                metadata: array_merge($request->metadata, [
                    'department_id' => $request->departmentId,
                    'justification' => $request->justification,
                    'urgency' => $request->urgency,
                ])
            );

            // 2. Initial budget reservation if possible
            $budgetCommitmentId = null;
            if ($this->budgetManager !== null && $request->budgetId !== null) {
                $budgetCommitmentId = $this->budgetManager->reserve(
                    tenantId: $request->tenantId,
                    budgetId: $request->budgetId,
                    amountCents: $requisition->getTotalAmountCents(),
                    referenceType: 'requisition',
                    referenceId: $requisition->getId()
                );
            }

            // 3. Initiate approval workflow
            $workflowResult = $this->approvalWorkflow->submitForApproval(
                tenantId: $request->tenantId,
                requisitionId: $requisition->getId(),
                requestorId: $request->requestedBy,
                requisitionData: [
                    'totalAmountCents' => $requisition->getTotalAmountCents(),
                    'departmentId' => $request->departmentId,
                    'categoryId' => $request->metadata['categoryId'] ?? 'general',
                ]
            );

            return RequisitionResult::success(
                requisitionId: $requisition->getId(),
                requisitionNumber: $requisition->getRequisitionNumber(),
                status: $requisition->getStatus()->value,
                totalAmountCents: $requisition->getTotalAmountCents(),
                budgetCommitmentId: $budgetCommitmentId,
                workflowInstanceId: $workflowResult->sagaInstanceId,
                message: 'Requisition created and submitted for approval.'
            );

        } catch (\Throwable $e) {
            $this->logger->error('Failed to create requisition via coordinator', [
                'error' => $e->getMessage(),
            ]);
            return RequisitionResult::failure($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function approve(string $tenantId, string $requisitionId, string $approvedBy, ?string $comments = null): RequisitionResult
    {
        try {
            $requisition = $this->requisitionQuery->findById($requisitionId);
            if (!$requisition) {
                throw RequisitionException::notFound($requisitionId);
            }

            // Resume workflow
            $workflowResult = $this->approvalWorkflow->processApprovalDecision(
                tenantId: $tenantId,
                requisitionId: $requisitionId,
                approverId: $approvedBy,
                decision: 'approve',
                comments: $comments
            );

            if (!$workflowResult->success) {
                return RequisitionResult::failure($workflowResult->errorMessage);
            }

            // Re-fetch requisition to get updated status
            $requisition = $this->requisitionQuery->findById($requisitionId);

            return RequisitionResult::success(
                requisitionId: $requisitionId,
                requisitionNumber: $requisition->getRequisitionNumber(),
                status: $requisition->getStatus()->value,
                totalAmountCents: $requisition->getTotalAmountCents()
            );

        } catch (\Throwable $e) {
            return RequisitionResult::failure($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function reject(string $tenantId, string $requisitionId, string $rejectedBy, string $reason): RequisitionResult
    {
         try {
            $requisition = $this->requisitionQuery->findById($requisitionId);
            if (!$requisition) {
                throw RequisitionException::notFound($requisitionId);
            }

            $workflowResult = $this->approvalWorkflow->processApprovalDecision(
                tenantId: $tenantId,
                requisitionId: $requisitionId,
                approverId: $rejectedBy,
                decision: 'reject',
                comments: $reason
            );

            if (!$workflowResult->success) {
                return RequisitionResult::failure($workflowResult->errorMessage);
            }

            $requisition = $this->requisitionQuery->findById($requisitionId);

            return RequisitionResult::success(
                requisitionId: $requisitionId,
                requisitionNumber: $requisition->getRequisitionNumber(),
                status: $requisition->getStatus()->value,
                totalAmountCents: $requisition->getTotalAmountCents()
            );

        } catch (\Throwable $e) {
            return RequisitionResult::failure($e->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function cancel(string $tenantId, string $requisitionId, string $cancelledBy, string $reason): RequisitionResult
    {
        try {
             $requisition = $this->requisitionQuery->findById($requisitionId);
            if (!$requisition) {
                throw RequisitionException::notFound($requisitionId);
            }

            $workflowResult = $this->approvalWorkflow->cancelApproval(
                tenantId: $tenantId,
                requisitionId: $requisitionId,
                cancelledBy: $cancelledBy,
                reason: $reason
            );

            if (!$workflowResult->success) {
                return RequisitionResult::failure($workflowResult->errorMessage);
            }

            $requisition = $this->requisitionQuery->findById($requisitionId);

            return RequisitionResult::success(
                requisitionId: $requisitionId,
                requisitionNumber: $requisition->getRequisitionNumber(),
                status: $requisition->getStatus()->value,
                totalAmountCents: $requisition->getTotalAmountCents()
            );
        } catch (\Throwable $e) {
            return RequisitionResult::failure($e->getMessage());
        }
    }
}
